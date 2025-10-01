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
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i LIMIT 1", $table), ARRAY_A);

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
    public function createTable(?string $currentDbVersion = null): bool {
        global $wpdb;
        $table = TableNames::getDeveloperInfo();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }

        $sql = $wpdb->prepare(
            "CREATE TABLE %i (
                %i int(11) NOT NULL AUTO_INCREMENT,
                %i varchar(255) NOT NULL,
                %i varchar(100),
                %i varchar(20),
                %i varchar(20),
                %i varchar(15),
                %i varchar(20),
                %i varchar(20),
                %i varchar(100),
                %i varchar(20),
                %i varchar(255),

                %i varchar(50),
                %i varchar(50),
                %i varchar(50),
                %i varchar(50),
                %i varchar(100),
                %i varchar(20),
                %i varchar(20),
                %i varchar(10),

                %i varchar(50),
                %i varchar(50),
                %i varchar(50),
                %i varchar(50),
                %i varchar(100),
                %i varchar(20),
                %i varchar(20),
                %i varchar(10),

                %i text,
                %i text,
                PRIMARY KEY (%i)
            ) " . $charset_collate,
            $table,
            SupplierDto::FIELD_ID,
            SupplierDto::FIELD_NAZWA,
            SupplierDto::FIELD_FORMA_PRAWNA,
            SupplierDto::FIELD_NR_KRS,
            SupplierDto::FIELD_NR_CEIDG,
            SupplierDto::FIELD_NR_NIP,
            SupplierDto::FIELD_NR_REGON,
            SupplierDto::FIELD_TELEFON,
            SupplierDto::FIELD_EMAIL,
            SupplierDto::FIELD_FAX,
            SupplierDto::FIELD_STRONA_WWW,
            SupplierDto::FIELD_SIEDZ_WOJEWODZTWO,
            SupplierDto::FIELD_SIEDZ_POWIAT,
            SupplierDto::FIELD_SIEDZ_GMINA,
            SupplierDto::FIELD_SIEDZ_MIEJSCOWOSC,
            SupplierDto::FIELD_SIEDZ_ULICA,
            SupplierDto::FIELD_SIEDZ_NR,
            SupplierDto::FIELD_SIEDZ_LOKAL,
            SupplierDto::FIELD_SIEDZ_KOD,
            SupplierDto::FIELD_SPRZED_WOJEWODZTWO,
            SupplierDto::FIELD_SPRZED_POWIAT,
            SupplierDto::FIELD_SPRZED_GMINA,
            SupplierDto::FIELD_SPRZED_MIEJSCOWOSC,
            SupplierDto::FIELD_SPRZED_ULICA,
            SupplierDto::FIELD_SPRZED_NR,
            SupplierDto::FIELD_SPRZED_LOKAL,
            SupplierDto::FIELD_SPRZED_KOD,
            SupplierDto::FIELD_DODATKOWE_LOKALIZACJE,
            SupplierDto::FIELD_SPOSOB_KONTAKTU,
            SupplierDto::FIELD_ID
        );

        return $wpdb->query($sql) !== false;
    }
}