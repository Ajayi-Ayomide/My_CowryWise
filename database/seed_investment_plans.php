<?php
require_once __DIR__ . '/../config/database.php';

$investment_plans = [
    [
        'name' => 'Safe Haven',
        'description' => 'Low-risk investment plan suitable for conservative investors looking for steady returns.',
        'minimum_amount' => 5000.00,
        'interest_rate' => 8.00,
        'duration_months' => 3,
        'risk_level' => 'low'
    ],
    [
        'name' => 'Growth Plus',
        'description' => 'Medium-risk investment plan offering balanced returns through diversified investments.',
        'minimum_amount' => 25000.00,
        'interest_rate' => 12.00,
        'duration_months' => 6,
        'risk_level' => 'medium'
    ],
    [
        'name' => 'Wealth Builder',
        'description' => 'High-risk, high-reward investment plan for aggressive investors seeking maximum returns.',
        'minimum_amount' => 100000.00,
        'interest_rate' => 18.00,
        'duration_months' => 12,
        'risk_level' => 'high'
    ]
];

try {
    foreach ($investment_plans as $plan) {
        $stmt = $conn->prepare("
            INSERT INTO investment_plans 
            (name, description, minimum_amount, interest_rate, duration_months, risk_level)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssddis",
            $plan['name'],
            $plan['description'],
            $plan['minimum_amount'],
            $plan['interest_rate'],
            $plan['duration_months'],
            $plan['risk_level']
        );
        
        $stmt->execute();
    }
    
    echo "Investment plans seeded successfully!\n";
} catch (Exception $e) {
    echo "Error seeding investment plans: " . $e->getMessage() . "\n";
}

$conn->close();
?> 