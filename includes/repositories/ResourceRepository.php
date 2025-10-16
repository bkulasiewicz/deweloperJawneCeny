<?php

namespace JawneCeny;

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

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT r.* FROM %i r ORDER BY r.%i ASC",
            $table,
            ResourceDto::FIELD_ID
        ), ARRAY_A);

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

        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE %i = %d",
            $table,
            ResourceDto::FIELD_ID,
            $id
        ), ARRAY_A);

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
        
        if (JawneCeny_SchemaManager::tableExists($table)) {
            $needsMigration = ($currentDbVersion === null) ||
                              ($currentDbVersion !== null && version_compare($currentDbVersion, '1.8', '<'));

            if ($needsMigration) {
                Logger::info('ResourceRepository: Migrating usable_area to DEFAULT NULL');

                $result = $wpdb->query($wpdb->prepare(
                    "ALTER TABLE %i MODIFY COLUMN %i decimal(8,2) DEFAULT NULL",
                    $table,
                    ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA
                ));

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
        $investmentTable = TableNames::getInvestmentInfo();

        $sql = $wpdb->prepare(
            "CREATE TABLE IF NOT EXISTS %i (
                %i int(11) NOT NULL AUTO_INCREMENT,
                %i int(11) NOT NULL DEFAULT 1,
                %i enum({$propertyEnum}) NOT NULL,
                %i varchar(50) NOT NULL,
                %i decimal(8,2) DEFAULT NULL,
                %i enum({$statusEnum}),

                %i varchar(100) DEFAULT NULL,
                %i varchar(50) DEFAULT NULL,
                %i decimal(10,2) DEFAULT NULL,
                %i datetime DEFAULT NULL,

                %i varchar(100) DEFAULT NULL,
                %i varchar(50) DEFAULT NULL,
                %i decimal(10,2) DEFAULT NULL,
                %i datetime DEFAULT NULL,

                %i text DEFAULT NULL,
                %i decimal(10,2) DEFAULT NULL,
                %i datetime DEFAULT NULL,

                %i text DEFAULT NULL,
                %i decimal(10,2) DEFAULT NULL,
                %i datetime DEFAULT NULL,

                %i int(11) DEFAULT NULL,
                %i int(11) DEFAULT NULL,
                %i text DEFAULT NULL,
                %i decimal(8,2) DEFAULT NULL,
                %i varchar(255) DEFAULT NULL,

                PRIMARY KEY (%i),
                KEY %i (%i),
                FOREIGN KEY (%i) REFERENCES %i(id) ON DELETE CASCADE
            ) " . $charset_collate, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $charset_collate is safe, from $wpdb->get_charset_collate()
            $table,
            ResourceDto::FIELD_ID,
            ResourceDto::FIELD_INVESTMENT_ID,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_STATUS,
            ResourceDto::FIELD_PROPERTY_PART_TITLE,
            ResourceDto::FIELD_PROPERTY_PART_DESIGNATION,
            ResourceDto::FIELD_PROPERTY_PART_PRICE,
            ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE,
            ResourceDto::FIELD_BELONGING_ROOM_TITLE,
            ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE,
            ResourceDto::FIELD_USAGE_RIGHT_TITLE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE,
            ResourceDto::FIELD_OTHER_SERVICE_TITLE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE,
            ResourceDto::FIELD_FLOOR_NUMBER,
            ResourceDto::FIELD_ROOM_COUNT,
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION,
            ResourceDto::FIELD_GARDEN_AREA,
            ResourceDto::FIELD_FLOOR_PLAN_PDF,
            ResourceDto::FIELD_ID,
            ResourceDto::FIELD_STATUS,
            ResourceDto::FIELD_STATUS,
            ResourceDto::FIELD_INVESTMENT_ID,
            $investmentTable
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is already prepared via $wpdb->prepare() above
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
            $sql .= ", p.total_price as total_price";
        }

        $sql .= " FROM `{$table}` r";

        // Join price history if needed for filtering or sorting by price
        $needsPriceJoin = ($filters && ($filters->priceMin !== null || $filters->priceMax !== null)) ||
                         ($sort && $sort->requiresPriceJoin());

        if ($needsPriceJoin) {
            $sql .= " LEFT JOIN (
                SELECT resource_id,
                       total_price
                FROM `{$priceTable}` p1
                WHERE p1.id = (
                    SELECT MAX(p2.id)
                    FROM `{$priceTable}` p2
                    WHERE p2.resource_id = p1.resource_id
                )
            ) p ON r.id = p.resource_id";
        }

        // Build WHERE clause
        $whereConditions = [];
        $prepareValues = [];

        if ($filters && $filters->hasFilters()) {
            // Price range filter
            if ($filters->priceMin !== null) {
                $whereConditions[] = "p.total_price >= %f";
                $prepareValues[] = $filters->priceMin;
            }

            if ($filters->priceMax !== null) {
                $whereConditions[] = "p.total_price <= %f";
                $prepareValues[] = $filters->priceMax;
            }

            // Area range filter
            if ($filters->areaMin !== null) {
                $whereConditions[] = "r.usable_area >= %f";
                $prepareValues[] = $filters->areaMin;
            }

            if ($filters->areaMax !== null) {
                $whereConditions[] = "r.usable_area <= %f";
                $prepareValues[] = $filters->areaMax;
            }

            // Status filter
            if (!empty($filters->statusFilter)) {
                $statusPlaceholders = implode(',', array_fill(0, count($filters->statusFilter), '%s'));
                $whereConditions[] = "r.status IN ({$statusPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->statusFilter);
            }

            // Floor filter
            if ($filters->floorFilter !== null) {
                $floorPlaceholders = implode(',', array_fill(0, count($filters->floorFilter), '%d'));
                $whereConditions[] = "r.floor_number IN ({$floorPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->floorFilter);
            }

            // Rooms filter
            if (!empty($filters->roomsFilter)) {
                $roomsPlaceholders = implode(',', array_fill(0, count($filters->roomsFilter), '%d'));
                $whereConditions[] = "r.room_count IN ({$roomsPlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->roomsFilter);
            }

            // Property types filter
            if (!empty($filters->propertyTypes)) {
                $typePlaceholders = implode(',', array_fill(0, count($filters->propertyTypes), '%s'));
                $whereConditions[] = "r.property_type IN ({$typePlaceholders})";
                $prepareValues = array_merge($prepareValues, $filters->propertyTypes);
            }

            // Unit number filter
            if (!empty($filters->unitNumberFilter)) {
                $unitPlaceholders = implode(',', array_fill(0, count($filters->unitNumberFilter), '%s'));
                $whereConditions[] = "r.unit_number IN ({$unitPlaceholders})";
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
            $sql .= " ORDER BY r.id ASC";
        }

        Logger::info("ResourceRepository::readAllWithFiltersAndSort - SQL: " . $sql);
        Logger::info("ResourceRepository::readAllWithFiltersAndSort - Prepare values: " . esc_html(print_r($prepareValues, true)));

        // Execute query
        if (!empty($prepareValues)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $prepareValues), ARRAY_A);
        } else {
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