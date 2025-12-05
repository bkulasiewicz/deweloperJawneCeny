<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

use JawneCeny\SettingsRepository;
use JawneCeny\DeveloperRepository;
use JawneCeny\InvestmentRepository;
use JawneCeny\ResourceRepository;
use JawneCeny\PriceHistoryRepository;
use JawneCeny\PublicationHistoryRepository;
use JawneCeny\XmlResourceRepository;

/**
 * Zarządzanie schematem bazy danych
 */
class JawneCeny_SchemaManager {
    
    /**
     * Tworzy wszystkie tabele wtyczki
     */
    public static function create_tables(): bool {
        global $wpdb;
        
        Logger::info('UJC_Schema_Manager::create_tables() - START');
        
        $charset_collate = $wpdb->get_charset_collate();
        Logger::info('UJC_Schema_Manager: Charset collate: ' . $charset_collate);
        
        // Jeden raz pobierz wersję dla wszystkich repositories
        $currentDbVersion = (new SettingsRepository())->getDbVersion();
        Logger::info('UJC_Schema_Manager: Current DB version from SettingsRepository: ' . ($currentDbVersion ?: 'NULL'));
        Logger::info('UJC_Schema_Manager: Expected JAWNECENY_DB_VERSION constant: ' . JAWNECENY_DB_VERSION);
        
        // Lista repositories do przetworzenia
        $repositories = [
            new DeveloperRepository(),
            new InvestmentRepository(),
            new ResourceRepository(),
            new PriceHistoryRepository(),
            new PublicationHistoryRepository(),
            new XmlResourceRepository()
        ];

        // Przetwarzaj każde repository
        foreach ($repositories as $repo) {
            if (!$repo->createTable($currentDbVersion)) {
                Logger::error('UJC_Schema_Manager: ' . get_class($repo) . ' failed: ' . $wpdb->last_error);
                return false;
            }
        }

        return true;
    }

    /**
     * Usuwa wszystkie tabele (dev only)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = [
            TableNames::getPriceHistory(),
            TableNames::getPublicationHistory(),
            TableNames::getXmlResource(),
            TableNames::getResources(),
            TableNames::getInvestmentInfo(),
            TableNames::getDeveloperInfo()
        ];

        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table));
        }
    }
    
    
    /**
     * Check if table exists
     */
    public static function tableExists(string $table): bool {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) == $table;
    }
    
    /**
     * Drop table if exists
     */
    public static function dropTable(string $table): bool {
        global $wpdb;
        return $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table)) !== false;
    }
    
    /**
     * Usuwa tabelę developer_info i odtwarza ją
     */
    public static function reset_developer_table() {
        self::dropTable(TableNames::getDeveloperInfo());
        (new DeveloperRepository())->createTable();
    }
    
    /**
     * Usuwa tabelę investment_info i odtwarza ją
     */
    public static function reset_investment_table() {
        self::dropTable(TableNames::getInvestmentInfo());
        (new InvestmentRepository())->createTable();
    }
    
    /**
     * Usuwa wszystkie tabele związane z zasobami i odtwarza je
     */
    public static function reset_resources_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Wyłącz sprawdzanie kluczy obcych
        $wpdb->query("SET FOREIGN_KEY_CHECKS=0");

        $tables = [
            TableNames::getPriceHistory(),
            TableNames::getResources()
        ];

        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table));
        }

        (new ResourceRepository())->createTable();
        (new PriceHistoryRepository())->createTable();
        self::create_foreign_keys($wpdb);

        // Włącz z powrotem sprawdzanie kluczy obcych
        $wpdb->query("SET FOREIGN_KEY_CHECKS=1");
    }
    
    /**
     * Usuwa tabelę publication_history i odtwarza ją
     */
    public static function reset_publication_history_table() {
        self::dropTable(TableNames::getPublicationHistory());
        (new PublicationHistoryRepository())->createTable();
    }
    
    /**
     * Usuwa tabelę xml_resources i odtwarza ją
     */
    public static function reset_xml_resource_table() {
        self::dropTable(TableNames::getXmlResource());
        (new XmlResourceRepository())->create();
    }
}