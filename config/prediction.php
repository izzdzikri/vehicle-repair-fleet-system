<?php

/**
 * Predictive Maintenance Engine Configuration
 * 
 * Research-Grade Configuration for Teraju Setia Enterprise Fleet Management System
 * Academic Supervisor: Dr. Nureize
 * 
 * Theoretical Basis:
 * 1. Physical Degradation Model: Combines calendar degradation with empirical mileage wear.
 * 2. Operating Environment Penalties: Severity factors adjust the nominal component lifespan.
 * 3. Confidence Calibration: Normalized margin functions from decision boundaries.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Rule-Based Baseline Engine (Deterministic Physics & Interval Heuristics)
    |--------------------------------------------------------------------------
    |
    | Pseudocode Reference for Thesis & Viva:
    | ---------------------------------------
    | Algorithm 1: Deterministic Multi-Constraint Maintenance Horizon
    | Input: x = {days_since, km_since, interval_km, interval_months, daily_km, terrain_severity}
    | Output: Urgency in {LOW, MEDIUM, HIGH}, Confidence in [50%, 100%]
    |
    | 1: effective_km <- interval_km / max(terrain_severity, 0.1)
    | 2: remaining_km <- effective_km - km_since
    | 3: days_via_km  <- remaining_km / max(daily_km, 1.0)
    | 4: days_via_time <- (interval_months * 30.44) - days_since
    | 5: d_remaining   <- min(days_via_km, days_via_time)
    | 6: if d_remaining <= 0 then
    | 7:    urgency <- 'HIGH'
    | 8:    confidence <- 50.0 + 50.0 * (min(abs(d_remaining), 60.0) / 60.0)
    | 9: else if d_remaining <= 14 then
    | 10:   urgency <- 'MEDIUM'
    | 11:   confidence <- 50.0 + 50.0 * (min(d_remaining, 14.0 - d_remaining) / 7.0)
    | 12: else
    | 13:   urgency <- 'LOW'
    | 14:   confidence <- 50.0 + 50.0 * (min(d_remaining - 14.0, 60.0) / 60.0)
    | 15: confidence <- clamp(confidence, 50.0, 99.0)
    | 16: return (urgency, confidence)
    |
    */
    'rule_based' => [
        // Threshold in remaining days for imminent inspection (Medium urgency)
        'due_soon_days' => 14,

        // Maximum boundary distance (days) used to saturate confidence to ~99%
        'confidence_margin_days' => 60,

        // Default daily driving rate when telemetry is unobserved
        'default_daily_km' => 40.0,

        // Clamping bounds for daily km rate
        'min_daily_km' => 10.0,
        'max_daily_km' => 180.0,

        // Terrain severity multiplier applied to component wear (shortens effective interval)
        'terrain_severity' => [
            'highway'  => 0.90, // Smooth paved highway with minimal stop-and-go
            'mixed'    => 1.00, // Balanced suburban / trunk road baseline
            'urban'    => 1.15, // Severe stop-and-go, thermal cycles, idling
            'rural'    => 1.20, // Unpaved roads, dust, suspension stress
            'mountain' => 1.30, // High gradient, heavy brake and transmission load
        ],

        // Fallback default service intervals when not specified in DB
        'service_defaults' => [
            'Oil & Filter Change'       => ['km' => 5000,  'months' => 6],
            'Full Engine Service'       => ['km' => 10000, 'months' => 12],
            'Tyre Rotation & Balancing' => ['km' => 10000, 'months' => 6],
            'Brake Pad Replacement'     => ['km' => 20000, 'months' => 18],
            'Suspension Inspection'     => ['km' => 25000, 'months' => 24],
            'Transmission Fluid Change' => ['km' => 40000, 'months' => 24],
            'Full Vehicle Inspection'   => ['km' => 30000, 'months' => 12],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard Model Hyperparameters
    |--------------------------------------------------------------------------
    |
    | Canonical configurations for the 4 statistical & machine learning classifiers.
    | All algorithms operate purely in PHP with standard, unhybridized architectures.
    |
    */
    'decision_tree' => [
        'max_depth'          => 6,
        'min_samples_split'  => 5,
        'criterion'          => 'gini', // Gini impurity minimization
    ],

    'random_forest' => [
        'n_estimators'       => 30, // 30 decorrelated CART trees for responsive PHP runtime
        'max_depth'          => 7,
        'min_samples_split'  => 4,
        'max_features_ratio' => 0.6, // Subset ratio of features examined per split (approx sqrt(d))
    ],

    'svm' => [
        'c'                  => 1.0,     // Regularization penalty parameter
        'gamma'              => 0.1,     // RBF kernel coefficient: exp(-gamma * ||x1 - x2||^2)
        'kernel'             => 'rbf',   // Standard Radial Basis Function
        'tolerance'          => 0.001,
        'max_iterations'     => 150,
    ],

    'logistic_regression' => [
        'learning_rate'      => 0.05,
        'max_iterations'     => 200,
        'l2_penalty'         => 0.01,    // Ridge regularization lambda
        'tolerance'          => 1e-4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Schema
    |--------------------------------------------------------------------------
    */
    'features' => [
        'numeric' => [
            'days_since_service',
            'km_since_service',
            'interval_km',
            'interval_months',
            'daily_km',
            'terrain_severity',
            'vehicle_age',
            'total_mileage',
        ],
        'categorical' => [
            'fuel_type',
            'owner_type',
        ],
        'classes' => [
            'LOW',
            'MEDIUM',
            'HIGH',
        ],
    ],

];
