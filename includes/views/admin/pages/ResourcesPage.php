<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona zasobów
 */
class ResourcesPage {

    private $getAllResourcesUseCase;
    private $resourceModal;
    private $investmentModal;
    private $developerRepository;
    private $investmentRepository;
    private $historyModal;

    public function __construct(
        GetAllResourcesUseCase $getAllResourcesUseCase,
        ResourceModal $resourceModal,
        InvestmentModal $investmentModal,
        DeveloperRepository $developerRepository,
        InvestmentRepository $investmentRepository,
        HistoryModal $historyModal
    ) {
        Logger::info('UJC: ResourcesPage constructor started with DI');

        $this->getAllResourcesUseCase = $getAllResourcesUseCase;
        $this->resourceModal = $resourceModal;
        $this->investmentModal = $investmentModal;
        $this->developerRepository = $developerRepository;
        $this->investmentRepository = $investmentRepository;
        $this->historyModal = $historyModal;

        // AJAX handlers - CSV import feature removed

        Logger::info('UJC: ResourcesPage initialized with DI successfully');
    }
    
    public function render() {
        $developer = $this->developerRepository->read();
        $investment = $this->investmentRepository->read();
        
        // Sprawdź czy dane dostawcy są wypełnione
        if (!$developer) {
            ?>
            <div class="wrap">
                <h1>Zasoby</h1>
                <div class="notice notice-warning">
                    <p>Najpierw musisz <a href="<?php echo esc_url(admin_url('admin.php?page=ujc-developer')); ?>">uzupełnić dane dostawcy</a>.</p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Jeśli brak inwestycji, pokaż przycisk dodawania inwestycji
        if (!$investment) {
            $this->render_no_investment_view();
            return;
        }
        
        // Jeśli inwestycja istnieje, pokaż formularz dodawania zasobów
        $this->render_resources_form($investment);
    }
    
    private function render_no_investment_view() {
        ?>
        <div class="wrap">
            <h1>Zasoby</h1>
            <div class="notice notice-info">
                <p><strong>Krok 1:</strong> Najpierw dodaj dane o inwestycji</p>
            </div>
            
            <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px dashed #ccc; margin: 20px 0;">
                <h2>Brak danych o inwestycji</h2>
                <p style="margin-bottom: 20px;">Aby dodać zasoby, musisz najpierw utworzyć inwestycję.</p>
                <button type="button" class="button-primary" onclick="openInvestmentModal()" style="font-size: 16px; padding: 8px 20px;">
                    <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; line-height: 1; margin-right: 5px;"></span>
                    Dodaj Inwestycję
                </button>
            </div>
            
            <!-- Renderuj modal inwestycji -->
            <?php $this->investmentModal->render_modal(); ?>
        </div>
        <?php
    }
    
    private function render_resources_form($investment) {
        $resourceModal = $this->resourceModal;
        $investmentModal = $this->investmentModal;
        
        ?>
        <div class="wrap">
            <h1>Zasoby</h1>
            
            <div class="notice notice-success">
                <p><strong>Krok 2:</strong> Teraz możesz dodać zasoby do zgłaszania</p>
            </div>
            
            <!-- Przycisk informacji o inwestycji -->
            <div style="margin: 20px 0;">
                <button type="button" class="button" onclick="openInvestmentModal(<?php echo $investment->id; ?>)" style="display: flex; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-info" style="font-size: 16px; line-height: 1;"></span>
                    <span>Dane Inwestycji</span>
                </button>
            </div>
            
            <!-- Nagłówek z przyciskami po prawej stronie -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <h2 style="margin: 0;">Lista Zasobów</h2>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="button-primary" onclick="openResourceModal('add')" style="display: flex; align-items: center; gap: 5px;">
                        <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; line-height: 1;"></span>
                        <span>Dodaj Zasób</span>
                    </button>
                </div>
            </div>
            
            <!-- Lista zasobów z ulepszonym layoutem -->
            <div id="resources-list" class="ujc-resources-grid">
                <?php $this->render_resources_list(); ?>
            </div>
            
            
            <!-- Renderuj modale -->
            <?php 
            $resourceModal->render_modal(); 
            $investmentModal->render_modal();
            ResourceItem::render_item_styles();
            ResourceItem::render_item_script();
            HistoryModal::render_history_script();
            ?>
            
            
            <script>
            jQuery(document).ready(function($) {
                // Lista renderowana w PHP - odświeżenie strony po zmianach
                window.loadResourcesList = function() {
                    location.reload();
                };
                
                // CSV import functionality removed
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Renderuje listę zasobów bezpośrednio w PHP
     */
    private function render_resources_list() {
        Logger::info("ResourcesPage::render_resources_list - Starting PHP rendering");
        
        try {
            Logger::info("ResourcesPage::render_resources_list - Calling getAllResourcesUseCase->execute()");
            $resources = $this->getAllResourcesUseCase->execute();
            Logger::info("ResourcesPage::render_resources_list - Got " . count($resources) . " resources from UseCase");
        } catch (Exception $e) {
            Logger::error("ResourcesPage::render_resources_list - Exception: " . $e->getMessage());
            ?>
            <div class="ujc-no-resources">
                <p>Błąd podczas pobierania zasobów: <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
            return;
        }
        
        if (empty($resources)) {
            Logger::info("ResourcesPage::render_resources_list - No resources found, showing empty state");
            ?>
            <div class="ujc-no-resources">
                <p>Brak zasobów. Kliknij "Dodaj Zasób" aby rozpocząć.</p>
            </div>
            <?php
            return;
        }
        
        Logger::info("ResourcesPage::render_resources_list - Rendering " . count($resources) . " resource items");
        foreach ($resources as $resource) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Resource item output contains safe onclick attributes
            echo ResourceItem::render_item_html($resource);
        }
        Logger::info("ResourcesPage::render_resources_list - PHP rendering completed successfully");
    }
}