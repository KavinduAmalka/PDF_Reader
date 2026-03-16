<?php

function getSupportedTemplates() {
    return [
        'standard' => [
            'path' => __DIR__ . '/templates/Software Requirements Specification (SRS) Template for Full process of the System.pdf',
            'name' => 'Software Requirements Specification (SRS) Template for Full process of the System.pdf',
            'label' => 'Download SRS Template - Full Process',
            'content_type' => 'application/pdf'
        ],
        'ieee-830' => [
            'path' => __DIR__ . '/templates/Software Requirements Specification (SRS) Template for subdivisions of the System.pdf',
            'name' => 'Software Requirements Specification (SRS) Template for subdivisions of the System.pdf',
            'label' => 'Download SRS Template - Subdivisions',
            'content_type' => 'application/pdf'
        ]
    ];
}
