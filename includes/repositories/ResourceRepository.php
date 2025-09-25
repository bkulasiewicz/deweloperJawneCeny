<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResourceRepository {
    
    /**
     * Get all resources
     */
    public function readAll() {
        $table = TableNames::getResources();
        global $wpdb;
        

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Field name constants are safe static strings
        $results = $wpdb->get_results("SELECT r.* FROM `{$table}` r ORDER BY r." . ResourceDto::FIELD_ID . " ASC", ARRAY_A);
        
        if ($wpdb->last_error) {
            Logger::error("ResourceRepository::readAll - SQL Error: " . $wpdb->last_error);
        }
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = ResourceDto::databaseToModel($row);
        }
        
        
        return $dtos;
    }
    
    /**
     * Get resource by ID
     */
    public function readById($id) {
        $table = TableNames::getResources();
        global $wpdb;

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Field name constants are safe static strings
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE " . ResourceDto::FIELD_ID . " = %d",
            $id
        ), ARRAY_A);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

        if (!$data) {
            return null;
        }

        return ResourceDto::databaseToModel($data);
    }
    
    /**
     * Create new resource
     */
    public function create(ResourceDto $dto): int {
        $table = TableNames::getResources();
        global $wpdb;
        
        
        $data = $dto->modelToDatabase();
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            Logger::error("ResourceRepository::create - Failed: " . $wpdb->last_error);
            Logger::error("ResourceRepository::create - Last query: " . $wpdb->last_query);
            throw new Exception('Failed to create resource: ' . esc_html($wpdb->last_error));
        }
        
        $insert_id = $wpdb->insert_id;
        
        return $insert_id;
    }
    
    /**
     * Update existing resource
     */
    public function update(ResourceDto $dto, int $id): void {
        $table = TableNames::getResources();
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        $result = $wpdb->update($table, $data, ['id' => $id]);
        
        if ($result === false) {
            throw new Exception('Failed to update resource: ' . esc_html($wpdb->last_error));
        }
    }
    
    /**
     * Delete resource
     */
    public function delete($id) {
        $table = TableNames::getResources();
        global $wpdb;
        
        return $wpdb->delete($table, ['id' => $id]);
    }
    
    /**
     * Create resources table using ResourceDto field constants
     */
    public function createTable(?string $currentDbVersion = null): bool {
        global $wpdb;
        $table = TableNames::getResources();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            $needsMigration = ($currentDbVersion === null) ||
                              ($currentDbVersion !== null && version_compare($currentDbVersion, '1.8', '<'));

            if ($needsMigration) {
                Logger::info('ResourceRepository: Migrating usable_area to DEFAULT NULL');

                $result = $wpdb->query("ALTER TABLE `{$table}` MODIFY COLUMN `usable_area` decimal(8,2) DEFAULT NULL");

                if ($result === false) {
                    Logger::error('ResourceRepository: Migration failed - ' . $wpdb->last_error);
                    return false;
                }
            }

            return true;
        }
        
        // Statyczne listy synchronizowane z enum keys
        $propertyTypes = [
            PropertyType::RESIDENTIAL_UNIT->value,
            PropertyType::SINGLE_FAMILY_HOUSE->value,
            PropertyType::SERVICE_PREMISES->value,
            PropertyType::PARKING_SPACE->value,
            PropertyType::STORAGE_ROOM->value,
            PropertyType::GARAGE->value
        ];
        
        $statusTypes = [
            ResourceStatus::AVAILABLE->value,
            ResourceStatus::RESERVED->value,
            ResourceStatus::SOLD->value
        ];
        
        $propertyEnum = "'" . implode("', '", $propertyTypes) . "'";
        $statusEnum = "'" . implode("', '", $statusTypes) . "'";
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            " . ResourceDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . ResourceDto::FIELD_INVESTMENT_ID . " int(11) NOT NULL DEFAULT 1,
            " . ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI . " enum({$propertyEnum}) NOT NULL,
            " . ResourceDto::FIELD_NR_LOKALU . " varchar(50) NOT NULL,
            " . ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA . " decimal(8,2) DEFAULT NULL,
            " . ResourceDto::FIELD_STATUS . " enum({$statusEnum}),
            
            " . ResourceDto::FIELD_PROPERTY_PART_TITLE . " varchar(100) DEFAULT NULL,
            " . ResourceDto::FIELD_PROPERTY_PART_DESIGNATION . " varchar(50) DEFAULT NULL,
            " . ResourceDto::FIELD_PROPERTY_PART_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_BELONGING_ROOM_TITLE . " varchar(100) DEFAULT NULL,
            " . ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION . " varchar(50) DEFAULT NULL,
            " . ResourceDto::FIELD_BELONGING_ROOM_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_USAGE_RIGHT_TITLE . " text DEFAULT NULL,
            " . ResourceDto::FIELD_USAGE_RIGHT_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_OTHER_SERVICE_TITLE . " text DEFAULT NULL,
            " . ResourceDto::FIELD_OTHER_SERVICE_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_FLOOR_NUMBER . " int(11) DEFAULT NULL,
            " . ResourceDto::FIELD_ROOM_COUNT . " int(11) DEFAULT NULL,
            " . ResourceDto::FIELD_ADDITIONAL_DESCRIPTION . " text DEFAULT NULL,
            " . ResourceDto::FIELD_GARDEN_AREA . " decimal(8,2) DEFAULT NULL,
            " . ResourceDto::FIELD_FLOOR_PLAN_PDF . " varchar(255) DEFAULT NULL,
            
            PRIMARY KEY (" . ResourceDto::FIELD_ID . "),
            KEY " . ResourceDto::FIELD_STATUS . " (" . ResourceDto::FIELD_STATUS . "),
            FOREIGN KEY (" . ResourceDto::FIELD_INVESTMENT_ID . ") REFERENCES " . TableNames::getInvestmentInfo() . "(id) ON DELETE CASCADE
        ) " . $charset_collate;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- CREATE TABLE statement with field constants is safe
        return $wpdb->query($sql) !== false;
    }

    /**
     * Get all resources with filtering and sorting
     */
    public function readAllWithFiltersAndSort(FilterCriteria $filters = null, SortOptions $sort = null): array {
        $table = TableNames::getResources();
        $priceTable = TableNames::getPriceHistory();
        global $wpdb;

        // Build base query
        $sql = "SELECT r.*";

        // Add price fields if needed for sorting
        if ($sort && $sort->requiresPriceJoin()) {
            $sql .= ", p." . PriceHistoryDto::FIELD_CENA_CALKOWITA . " as total_price";
        }

        $sql .= " FROM `{$table}` r";

        // Join price history if needed for filtering or sorting by price
        $needsPriceJoin = ($filters && ($filters->priceMin !== null || $filters->priceMax !== null)) ||
                         ($sort && $sort->requiresPriceJoin());

        if ($needsPriceJoin) {
            $sql .= " LEFT JOIN (
                SELECT " . PriceHistoryDto::FIELD_RESOURCE_ID . ",
                       " . PriceHistoryDto::FIELD_CENA_CALKOWITA . "
                FROM `{$priceTable}` p1
                WHERE p1." . PriceHistoryDto::FIELD_ID . " = (
                    SELECT MAX(p2." . PriceHistoryDto::FIELD_ID . ")
                    FROM `{$priceTable}` p2
                    WHERE p2." . PriceHistoryDto::FIELD_RESOURCE_ID . " = p1." . PriceHistoryDto::FIELD_RESOURCE_ID . "
                )
            ) p ON r." . ResourceDto::FIELD_ID . " = p." . PriceHistoryDto::FIELD_RESOURCE_ID;
        }

        // Build WHERE clause
        $whereConditions = [];
        $prepareValues = [];

        if ($filters && $filters->hasFilters()) {
            // Price range filter
            if ($filters->priceMin !== null) {
                $whereConditions[] = "p." . PriceHistoryDto::FIELD_CENA_CALKOWITA . " >= %f";
                $prepareValues[] = $filters->priceMin;
            }

            if ($filters->priceMax !== null) {
                $whereConditions[] = "p." . PriceHistoryDto::FIELD_CENA_CALKOWITA . " <= %f";
                $prepareValues[] = $filters->priceMax;
            }

            // Area range filter
            if ($filters->areaMin !== null) {
                $whereConditions[] = "r." . ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA . " >= %f";
                $prepareValues[] = $filters->areaMin;
            }

            if ($filters->areaMax !== null) {
                $whereConditions[] = "r." . ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA . " <= %f";
                $prepareValues[] = $filters->areaMax;
            }

            // Status filter
            if (!empty($filters->statusFilter)) {
                $statusPlaceholders = implode(',', array_fill(0, count($filters->statusFilter), '%s'));
                $whereConditions[] = "r." . ResourceDto::FIELD_STATUS . " IN ({$statusPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->statusFilter);
            }

            // Floor filter
            if (!empty($filters->floorFilter)) {
                $floorPlaceholders = implode(',', array_fill(0, count($filters->floorFilter), '%d'));
                $whereConditions[] = "r." . ResourceDto::FIELD_FLOOR_NUMBER . " IN ({$floorPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->floorFilter);
            }

            // Rooms filter
            if (!empty($filters->roomsFilter)) {
                $roomsPlaceholders = implode(',', array_fill(0, count($filters->roomsFilter), '%d'));
                $whereConditions[] = "r." . ResourceDto::FIELD_ROOM_COUNT . " IN ({$roomsPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->roomsFilter);
            }

            // Property types filter
            if (!empty($filters->propertyTypes)) {
                $typePlaceholders = implode(',', array_fill(0, count($filters->propertyTypes), '%s'));
                $whereConditions[] = "r." . ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI . " IN ({$typePlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->propertyTypes);
            }

            // Unit number filter
            if (!empty($filters->unitNumberFilter)) {
                $unitPlaceholders = implode(',', array_fill(0, count($filters->unitNumberFilter), '%s'));
                $whereConditions[] = "r." . ResourceDto::FIELD_NR_LOKALU . " IN ({$unitPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->unitNumberFilter);
            }
        }

        // Add WHERE clause if needed
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        // Add ORDER BY clause
        if ($sort && $sort->sortBy) {
            $orderField = $sort->getSqlFieldName();
            if ($orderField) {
                if ($sort->requiresPriceJoin()) {
                    $sql .= " ORDER BY total_price " . $sort->sortOrder;
                } else {
                    $sql .= " ORDER BY r." . $orderField . " " . $sort->sortOrder;
                }
            }
        } else {
            // Default ordering
            $sql .= " ORDER BY r." . ResourceDto::FIELD_ID . " ASC";
        }

        Logger::info("ResourceRepository::readAllWithFiltersAndSort - SQL: " . $sql);
        Logger::info("ResourceRepository::readAllWithFiltersAndSort - Prepare values: " . print_r($prepareValues, true));

        // Execute query
        if (!empty($prepareValues)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic SQL is built safely with constants and prepared values
            $results = $wpdb->get_results($wpdb->prepare($sql, $prepareValues), ARRAY_A);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is built safely with constants
            $results = $wpdb->get_results($sql, ARRAY_A);
        }

        if ($wpdb->last_error) {
            Logger::error("ResourceRepository::readAllWithFiltersAndSort - SQL Error: " . $wpdb->last_error);
        }

        $dtos = [];
        foreach($results as $row) {
            $dtos[] = ResourceDto::databaseToModel($row);
        }

        Logger::info("ResourceRepository::readAllWithFiltersAndSort - Found " . count($dtos) . " resources");
        return $dtos;
    }

}