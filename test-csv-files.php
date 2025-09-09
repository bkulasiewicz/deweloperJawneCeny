<?php
/**
 * Skrypt testowy do dodania przykładowych plików CSV do bazy danych
 * Uruchom ten plik przez WP CLI lub dodaj do functions.php tymczasowo
 */

// Dodaj testowe dane XML resources
function add_test_xml_resources() {
    $xmlResourceRepo = new XmlResourceRepository();
    
    // Testowe dane z różnymi datami
    $testData = [
        [
            'ext_ident' => 'test_' . date('YmdHis') . '_001',
            'csv_url' => wp_upload_dir()['baseurl'] . '/ujc-data/test-2024-01-01.csv',
            'data_date' => '2024-01-01'
        ],
        [
            'ext_ident' => 'test_' . date('YmdHis') . '_002', 
            'csv_url' => wp_upload_dir()['baseurl'] . '/ujc-data/test-2024-01-02.csv',
            'data_date' => '2024-01-02'
        ],
        [
            'ext_ident' => 'test_' . date('YmdHis') . '_003',
            'csv_url' => wp_upload_dir()['baseurl'] . '/ujc-data/test-today.csv', 
            'data_date' => date('Y-m-d')
        ]
    ];
    
    foreach ($testData as $data) {
        $dto = new XmlResourceDto(
            null,
            $data['ext_ident'],
            $data['csv_url'],
            $data['data_date'],
            date('Y-m-d H:i:s')
        );
        
        $result = $xmlResourceRepo->addResource($dto);
        echo "Dodano testowy rekord: " . ($result ? "SUKCES" : "BŁĄD") . "\n";
    }
}

// Uruchom funkcję
add_test_xml_resources();