<?php

if (!defined('ABSPATH')) {
    exit;
}

class DeveloperRepository {
    
    /**
     * Get developer data
     */
    public function read(): ?SupplierDto {
        $table = TableNames::getDeveloperInfo();        
        global $wpdb;
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` LIMIT 1"), ARRAY_A);
        
        return $data ? SupplierDto::databaseToModel($data) : null;
    }
    
    /**
     * Save developer data (create or update)
     */
    public function save(SupplierDto $dto) {
        $table = TableNames::getDeveloperInfo();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        // Check if record already exists
        $existing = $this->read();
        
        if ($existing) {
            // Update existing
            return $wpdb->update($table, $data, ['id' => $existing->id]);
        } else {
            // Create new
            return $wpdb->insert($table, $data);
        }
    }
    
    /**
     * Create developer table using SupplierDto field constants
     */
    public function createTable(string $currentDbVersion): bool {
        global $wpdb;
        $table = TableNames::getDeveloperInfo();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }
        
        $sql = "CREATE TABLE `{$table}` (
            " . SupplierDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . SupplierDto::FIELD_NAZWA . " varchar(255) NOT NULL,
            " . SupplierDto::FIELD_FORMA_PRAWNA . " varchar(100),
            " . SupplierDto::FIELD_NR_KRS . " varchar(20),
            " . SupplierDto::FIELD_NR_CEIDG . " varchar(20),
            " . SupplierDto::FIELD_NR_NIP . " varchar(15),
            " . SupplierDto::FIELD_NR_REGON . " varchar(20),
            " . SupplierDto::FIELD_TELEFON . " varchar(20),
            " . SupplierDto::FIELD_EMAIL . " varchar(100),
            " . SupplierDto::FIELD_FAX . " varchar(20),
            " . SupplierDto::FIELD_STRONA_WWW . " varchar(255),
            
            " . SupplierDto::FIELD_SIEDZ_WOJEWODZTWO . " varchar(50),
            " . SupplierDto::FIELD_SIEDZ_POWIAT . " varchar(50),
            " . SupplierDto::FIELD_SIEDZ_GMINA . " varchar(50),
            " . SupplierDto::FIELD_SIEDZ_MIEJSCOWOSC . " varchar(50),
            " . SupplierDto::FIELD_SIEDZ_ULICA . " varchar(100),
            " . SupplierDto::FIELD_SIEDZ_NR . " varchar(20),
            " . SupplierDto::FIELD_SIEDZ_LOKAL . " varchar(20),
            " . SupplierDto::FIELD_SIEDZ_KOD . " varchar(10),
            
            " . SupplierDto::FIELD_SPRZED_WOJEWODZTWO . " varchar(50),
            " . SupplierDto::FIELD_SPRZED_POWIAT . " varchar(50),
            " . SupplierDto::FIELD_SPRZED_GMINA . " varchar(50),
            " . SupplierDto::FIELD_SPRZED_MIEJSCOWOSC . " varchar(50),
            " . SupplierDto::FIELD_SPRZED_ULICA . " varchar(100),
            " . SupplierDto::FIELD_SPRZED_NR . " varchar(20),
            " . SupplierDto::FIELD_SPRZED_LOKAL . " varchar(20),
            " . SupplierDto::FIELD_SPRZED_KOD . " varchar(10),
            
            " . SupplierDto::FIELD_DODATKOWE_LOKALIZACJE . " text,
            " . SupplierDto::FIELD_SPOSOB_KONTAKTU . " text,
            PRIMARY KEY (" . SupplierDto::FIELD_ID . ")
        ) " . $charset_collate;
        
        return $wpdb->query($wpdb->prepare($sql)) !== false;
    }
}