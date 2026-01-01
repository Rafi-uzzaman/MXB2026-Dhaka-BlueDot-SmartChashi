<?php

if (!isLoggedIn()) {
    redirect('login');
}
    include __DIR__ . '/../layouts/header.php';

$db = new Database();
$user = getCurrentUser();

// Get user's region from farmer profile if available
$userRegion = 'Dhaka'; // Default
$userCrops = [];
if ($user['role'] === 'farmer') {
    $farmerProfile = $db->single("SELECT region, district, location_lat, location_lng FROM farmer_profiles WHERE user_id = ?", [$user['user_id']]);
    if ($farmerProfile && !empty($farmerProfile['region'])) {
        $userRegion = $farmerProfile['region'];
    }
    // Get user's crops for personalized recommendations
    $crops = $db->resultSet("SELECT crop_name, crop_type FROM crop_data WHERE farmer_id = ? AND status IN ('planning', 'growing')", [$user['user_id']]);
    $userCrops = $crops ? array_column($crops, 'crop_name') : [];
}

// Bangladesh regions with coordinates
$regions = [
    'Dhaka' => ['lat' => 23.8103, 'lon' => 90.4125],
    'Chittagong' => ['lat' => 22.3569, 'lon' => 91.7832],
    'Khulna' => ['lat' => 22.8456, 'lon' => 89.5403],
    'Rajshahi' => ['lat' => 24.3745, 'lon' => 88.6042],
    'Rangpur' => ['lat' => 25.7439, 'lon' => 89.2752],
    'Sylhet' => ['lat' => 24.8949, 'lon' => 91.8687],
    'Barisal' => ['lat' => 22.7010, 'lon' => 90.3535],
    'Mymensingh' => ['lat' => 24.7471, 'lon' => 90.4203]
];

// Get active weather alerts from database
$weatherAlerts = $db->resultSet("SELECT * FROM alerts WHERE alert_type = 'weather' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC LIMIT 5");

// Get farming advisories
$farmingAdvisories = $db->resultSet("SELECT * FROM advisories WHERE advisory_type IN ('weather', 'seasonal', 'general') AND is_active = 1 ORDER BY created_at DESC LIMIT 3");

// Groq API key for AI recommendations (free tier available)
$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';

// User's registered phone for SMS alerts
$userPhone = !empty($user['phone']) ? $user['phone'] : '';

// API configurations from config
$apiConfig = [
    'openMeteo' => defined('OPEN_METEO_API') ? OPEN_METEO_API : 'https://api.open-meteo.com/v1/forecast',
    'openMeteoAirQuality' => defined('OPEN_METEO_AIR_QUALITY_API') ? OPEN_METEO_AIR_QUALITY_API : 'https://air-quality-api.open-meteo.com/v1/air-quality',
    'nasaEonet' => defined('NASA_EONET_API') ? NASA_EONET_API : 'https://eonet.gsfc.nasa.gov/api/v3/events',
    'rainviewer' => defined('RAINVIEWER_API') ? RAINVIEWER_API : 'https://api.rainviewer.com/public/weather-maps.json',
    'nasaGibs' => defined('NASA_GIBS_API') ? NASA_GIBS_API : 'https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi',
    'windyEmbed' => defined('WINDY_EMBED_URL') ? WINDY_EMBED_URL : 'https://embed.windy.com/embed2.html',
    'groqApi' => defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions',
    'groqModel' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant'
];
?>

<section class="hero">
    <h1><span class="material-icons">wb_cloudy</span> <?php echo __('weather_alerts_title'); ?></h1>
    <p><?php echo __('realtime_weather_info'); ?></p>
</section>

<!-- Quick Stats Row -->
<div class="weather-quick-stats">
    <div class="stat-card" id="statTemp">
        <span class="material-icons">thermostat</span>
        <div class="stat-value">--°C</div>
        <div class="stat-label">Temperature</div>
    </div>
    <div class="stat-card" id="statHumidity">
        <span class="material-icons">water_drop</span>
        <div class="stat-value">--%</div>
        <div class="stat-label">Humidity</div>
    </div>
    <div class="stat-card" id="statUV">
        <span class="material-icons">wb_sunny</span>
        <div class="stat-value">--</div>
        <div class="stat-label">UV Index</div>
    </div>
    <div class="stat-card" id="statAQI">
        <span class="material-icons">air</span>
        <div class="stat-value">--</div>
        <div class="stat-label">Air Quality</div>
    </div>
    <div class="stat-card" id="statRain">
        <span class="material-icons">grain</span>
        <div class="stat-value">-- mm</div>
        <div class="stat-label">Precipitation</div>
    </div>
    <div class="stat-card" id="statWind">
        <span class="material-icons">air</span>
        <div class="stat-value">-- km/h</div>
        <div class="stat-label">Wind Speed</div>
    </div>
</div>

<div class="weather-grid">
    <div class="weather-card-wrapper">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="material-icons" style="vertical-align: middle;">wb_cloudy</span>
                    <?php echo __('current_weather'); ?>
                </h3>
            </div>

            <div id="weatherData" class="weather-data-display">
                <div class="weather-loading">
                    <span class="material-icons spinning">sync</span>
                    <p>Loading weather data...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="weather-card-wrapper">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="material-icons" style="vertical-align: middle;">location_on</span>
                    <?php echo __('location'); ?>
                </h3>
            </div>

            <div class="form-group">
                <label for="region"><?php echo __('your_region'); ?></label>
                <select id="region" name="region" onchange="changeRegion()">
                    <?php foreach ($regions as $name => $coords): ?>
                    <option value="<?php echo $name; ?>" data-lat="<?php echo $coords['lat']; ?>" data-lon="<?php echo $coords['lon']; ?>" <?php echo $name === $userRegion ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn btn-detect-location btn-block" onclick="detectLocation()">
                <span class="material-icons" style="vertical-align: middle; font-size: 18px;">my_location</span>
                <?php echo __('auto_detect_location'); ?>
            </button>

            <div id="locationInfo" class="location-info mt-3" style="display: none;">
                <p><span class="material-icons">place</span> <strong>Coordinates:</strong></p>
                <p class="text-small"><strong>Lat:</strong> <span id="locationLat">-</span></p>
                <p class="text-small"><strong>Lon:</strong> <span id="locationLng">-</span></p>
            </div>
            
            <div class="weather-updated mt-3">
                <small><span class="material-icons">update</span> Last updated: <span id="lastUpdated">-</span></small>
            </div>
        </div>
    </div>
</div>

<!-- 7-Day Forecast -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">calendar_today</span>
            <?php echo __('day_forecast'); ?>
        </h3>
    </div>
    <div id="forecastContainer" class="forecast-grid">
        <div class="forecast-loading">
            <span class="material-icons spinning">sync</span>
            <p>Loading forecast...</p>
        </div>
    </div>
</div>

<!-- Hourly Forecast -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">schedule</span>
            24-Hour Forecast
        </h3>
    </div>
    <div id="hourlyContainer" class="hourly-forecast-scroll">
        <div class="forecast-loading">
            <span class="material-icons spinning">sync</span>
            <p>Loading...</p>
        </div>
    </div>
</div>

<!-- Weather Alerts -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #ff9800;">warning</span>
            Active Weather Alerts
        </h3>
    </div>
    <div id="alertsContainer" class="alerts-container">
    <?php if ($weatherAlerts): ?>
        <?php foreach ($weatherAlerts as $alert): ?>
        <div class="notice notice-<?php echo $alert['priority'] === 'high' || $alert['priority'] === 'critical' ? 'warning' : ($alert['priority'] === 'medium' ? 'info' : 'success'); ?>">
            <div class="alert-content">
                <div class="alert-header">
                    <span class="material-icons"><?php echo $alert['priority'] === 'high' ? 'warning' : 'info'; ?></span>
                    <strong><?php echo htmlspecialchars($alert['title']); ?></strong>
                    <span class="badge badge-<?php echo $alert['priority'] === 'high' ? 'danger' : 'info'; ?>"><?php echo ucfirst($alert['priority']); ?></span>
                </div>
                <p><?php echo htmlspecialchars($alert['message']); ?></p>
                <small>Issued: <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="notice notice-success">
            <div class="alert-content">
                <div class="alert-header">
                    <span class="material-icons">check_circle</span>
                    <strong>No Active Alerts</strong>
                </div>
                <p>No weather alerts for your region at this time.</p>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- NASA/GDACS Natural Disaster Alerts -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #e74c3c;">public</span>
            Global Disaster Monitoring
            <span class="badge badge-info">NASA EONET + GDACS</span>
        </h3>
    </div>
    <div id="disasterAlertsContainer" class="disaster-alerts-container">
        <div class="forecast-loading">
            <span class="material-icons spinning">sync</span>
            <p>Fetching disaster data from NASA & GDACS...</p>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted">
            <span class="material-icons" style="font-size: 14px;">info</span>
            Data sources: NASA Earth Observatory Natural Event Tracker (EONET), Global Disaster Alert Coordination System (GDACS)
        </small>
    </div>
</div>

<!-- Satellite & Radar Section -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #9b59b6;">satellite_alt</span>
            Satellite & Radar Imagery
        </h3>
    </div>
    <div class="satellite-section">
        <div class="satellite-tabs">
            <button class="satellite-tab active" onclick="showSatelliteTab('rainfall')">
                <span class="material-icons">water_drop</span> Rainfall Radar
            </button>
            <button class="satellite-tab" onclick="showSatelliteTab('clouds')">
                <span class="material-icons">cloud</span> Cloud Cover
            </button>
            <button class="satellite-tab" onclick="showSatelliteTab('vegetation')">
                <span class="material-icons">grass</span> Vegetation (NDVI)
            </button>
            <button class="satellite-tab" onclick="showSatelliteTab('temperature')">
                <span class="material-icons">thermostat</span> Temperature Map
            </button>
        </div>
        <div id="satelliteImageContainer" class="satellite-image-container">
            <div class="satellite-loading">
                <span class="material-icons spinning">satellite_alt</span>
                <p>Loading satellite imagery...</p>
            </div>
        </div>
        <div class="satellite-info">
            <small>
                <span class="material-icons">schedule</span> Updated every 15 minutes | 
                <span class="material-icons">location_on</span> Centered on <span id="satelliteLocation">Bangladesh</span>
            </small>
        </div>
    </div>
</div>

<!-- AI-Powered Farming Recommendations -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: var(--primary);">psychology</span>
            AI-Powered Farming Recommendations
            <span class="badge badge-success">Smart Analysis</span>
        </h3>
        <button class="btn btn-sm btn-outline" onclick="getAIRecommendations()" id="aiRefreshBtn">
            <span class="material-icons">auto_awesome</span> Get AI Insights
        </button>
    </div>
    
    <!-- AI Summary -->
    <div id="aiSummaryContainer" class="ai-summary-container">
        <div class="ai-summary-loading">
            <span class="material-icons spinning">psychology</span>
            <p>AI is analyzing weather patterns...</p>
        </div>
    </div>
    
    <div id="recommendationsContainer" class="recommendations-grid">
    <div class="card recommendation-card recommended">
        <h4>
            <span class="material-icons">check_circle</span>
            Recommended Today
        </h4>
        <ul class="recommendation-list" id="recommendedList">
            <li><span class="material-icons spinning">sync</span> Loading...</li>
        </ul>
    </div>

    <div class="card recommendation-card not-recommended">
        <h4>
            <span class="material-icons">pause_circle</span>
            Not Recommended
        </h4>
        <ul class="recommendation-list" id="notRecommendedList">
            <li><span class="material-icons spinning">sync</span> Loading...</li>
        </ul>
    </div>

    <div class="card recommendation-card precaution">
        <h4>
            <span class="material-icons">warning</span>
            Take Precautions
        </h4>
        <ul class="recommendation-list" id="precautionsList">
            <li><span class="material-icons spinning">sync</span> Loading...</li>
        </ul>
    </div>
    </div>
</div>

<!-- Crop-Specific Recommendations -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #27ae60;">eco</span>
            Crop-Specific Weather Impact
        </h3>
    </div>
    <div id="cropRecommendationsContainer" class="crop-recommendations-container">
        <div class="forecast-loading">
            <span class="material-icons spinning">sync</span>
            <p>Analyzing impact on crops...</p>
        </div>
    </div>
</div>

<!-- Weather History Chart -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title"><span class="material-icons">show_chart</span> Weather Trends (7 Days)</h3>
    </div>
    <div id="weatherChart" class="weather-chart">
        <canvas id="tempChart"></canvas>
    </div>
</div>

<!-- Agricultural Index Card -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #f39c12;">analytics</span>
            Agricultural Weather Index
        </h3>
    </div>
    <div id="agriIndexContainer" class="agri-index-container">
        <div class="agri-index-grid">
            <div class="agri-index-card" id="gddIndex">
                <div class="index-icon"><span class="material-icons">thermostat</span></div>
                <div class="index-value">--</div>
                <div class="index-label">Growing Degree Days</div>
                <div class="index-status">Calculating...</div>
            </div>
            <div class="agri-index-card" id="etIndex">
                <div class="index-icon"><span class="material-icons">opacity</span></div>
                <div class="index-value">-- mm</div>
                <div class="index-label">Evapotranspiration</div>
                <div class="index-status">Calculating...</div>
            </div>
            <div class="agri-index-card" id="soilMoistureIndex">
                <div class="index-icon"><span class="material-icons">grass</span></div>
                <div class="index-value">--%</div>
                <div class="index-label">Soil Moisture</div>
                <div class="index-status">Calculating...</div>
            </div>
            <div class="agri-index-card" id="frostRiskIndex">
                <div class="index-icon"><span class="material-icons">ac_unit</span></div>
                <div class="index-value">--</div>
                <div class="index-label">Frost Risk</div>
                <div class="index-status">Calculating...</div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Weather Metrics -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #e74c3c;">speed</span>
            Advanced Weather Metrics
        </h3>
    </div>
    <div class="advanced-metrics-container">
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-visual">
                    <div class="wind-compass" id="windCompass">
                        <div class="compass-rose">
                            <span class="compass-dir n">N</span>
                            <span class="compass-dir e">E</span>
                            <span class="compass-dir s">S</span>
                            <span class="compass-dir w">W</span>
                        </div>
                        <div class="compass-needle"></div>
                        <div class="compass-center"></div>
                    </div>
                </div>
                <div class="metric-info">
                    <h4>Wind Direction</h4>
                    <p class="metric-value" id="windDirection">--°</p>
                    <p class="metric-label" id="windDirectionLabel">Loading...</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-gauge">
                    <svg viewBox="0 0 100 60" class="gauge-svg">
                        <path d="M10 50 A40 40 0 0 1 90 50" fill="none" stroke="#eee" stroke-width="8"/>
                        <path d="M10 50 A40 40 0 0 1 90 50" fill="none" stroke="url(#heatGradient)" stroke-width="8" id="heatIndexArc" stroke-dasharray="0 126"/>
                        <defs>
                            <linearGradient id="heatGradient">
                                <stop offset="0%" stop-color="#3498db"/>
                                <stop offset="50%" stop-color="#f1c40f"/>
                                <stop offset="100%" stop-color="#e74c3c"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="metric-info">
                    <h4>Heat Index</h4>
                    <p class="metric-value" id="heatIndex">--°C</p>
                    <p class="metric-label" id="heatIndexLabel">Feels like temperature</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-gauge">
                    <svg viewBox="0 0 100 60" class="gauge-svg">
                        <path d="M10 50 A40 40 0 0 1 90 50" fill="none" stroke="#eee" stroke-width="8"/>
                        <path d="M10 50 A40 40 0 0 1 90 50" fill="none" stroke="url(#dewGradient)" stroke-width="8" id="dewPointArc" stroke-dasharray="0 126"/>
                        <defs>
                            <linearGradient id="dewGradient">
                                <stop offset="0%" stop-color="#74b9ff"/>
                                <stop offset="100%" stop-color="#0984e3"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="metric-info">
                    <h4>Dew Point</h4>
                    <p class="metric-value" id="dewPoint">--°C</p>
                    <p class="metric-label" id="dewPointLabel">Moisture comfort level</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-visual visibility-visual">
                    <div class="visibility-indicator" id="visibilityIndicator">
                        <span class="material-icons">visibility</span>
                    </div>
                </div>
                <div class="metric-info">
                    <h4>Visibility</h4>
                    <p class="metric-value" id="visibility">-- km</p>
                    <p class="metric-label" id="visibilityLabel">Checking...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pest & Disease Risk Prediction -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #9b59b6;">bug_report</span>
            Pest & Disease Risk Prediction
            <span class="badge badge-warning">AI Analysis</span>
        </h3>
    </div>
    <div class="pest-risk-container">
        <div class="risk-summary" id="pestRiskSummary">
            <div class="risk-level-indicator" id="overallPestRisk">
                <div class="risk-circle">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#eee" stroke-width="8"/>
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#27ae60" stroke-width="8" 
                                stroke-dasharray="283" stroke-dashoffset="70" id="riskCircle"
                                transform="rotate(-90 50 50)"/>
                    </svg>
                    <div class="risk-value">
                        <span id="riskPercentage">--</span>%
                        <small>Risk Level</small>
                    </div>
                </div>
            </div>
            <div class="risk-details">
                <h4>Current Conditions Analysis</h4>
                <p id="riskAnalysisText">Analyzing weather conditions for pest and disease risks...</p>
            </div>
        </div>
        <div class="pest-categories">
            <div class="pest-category" id="fungalRisk">
                <div class="pest-icon"><span class="material-icons">spa</span></div>
                <div class="pest-info">
                    <h5>Fungal Diseases</h5>
                    <div class="risk-bar">
                        <div class="risk-fill" style="width: 0%"></div>
                    </div>
                    <span class="risk-text">Low</span>
                </div>
            </div>
            <div class="pest-category" id="insectRisk">
                <div class="pest-icon"><span class="material-icons">pest_control</span></div>
                <div class="pest-info">
                    <h5>Insect Activity</h5>
                    <div class="risk-bar">
                        <div class="risk-fill" style="width: 0%"></div>
                    </div>
                    <span class="risk-text">Low</span>
                </div>
            </div>
            <div class="pest-category" id="bacterialRisk">
                <div class="pest-icon"><span class="material-icons">coronavirus</span></div>
                <div class="pest-info">
                    <h5>Bacterial Diseases</h5>
                    <div class="risk-bar">
                        <div class="risk-fill" style="width: 0%"></div>
                    </div>
                    <span class="risk-text">Low</span>
                </div>
            </div>
            <div class="pest-category" id="weedRisk">
                <div class="pest-icon"><span class="material-icons">grass</span></div>
                <div class="pest-info">
                    <h5>Weed Growth</h5>
                    <div class="risk-bar">
                        <div class="risk-fill" style="width: 0%"></div>
                    </div>
                    <span class="risk-text">Low</span>
                </div>
            </div>
        </div>
        <div class="pest-alerts" id="pestAlerts">
            <!-- Populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Smart Irrigation Calculator -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #3498db;">water</span>
            Smart Irrigation Calculator
        </h3>
    </div>
    <div class="irrigation-container">
        <div class="irrigation-inputs">
            <div class="input-group">
                <label><span class="material-icons">straighten</span> Field Size</label>
                <div class="input-with-unit">
                    <input type="number" id="fieldSize" value="1" min="0.1" step="0.1">
                    <select id="fieldUnit">
                        <option value="hectare">Hectares</option>
                        <option value="acre">Acres</option>
                        <option value="bigha">Bigha</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label><span class="material-icons">eco</span> Crop Type</label>
                <select id="irrigationCrop">
                    <option value="rice">Rice (Paddy)</option>
                    <option value="wheat">Wheat</option>
                    <option value="vegetables">Vegetables</option>
                    <option value="sugarcane">Sugarcane</option>
                    <option value="jute">Jute</option>
                    <option value="maize">Maize</option>
                    <option value="potato">Potato</option>
                </select>
            </div>
            <div class="input-group">
                <label><span class="material-icons">settings</span> Irrigation System</label>
                <select id="irrigationSystem">
                    <option value="flood">Flood Irrigation</option>
                    <option value="drip">Drip Irrigation</option>
                    <option value="sprinkler">Sprinkler</option>
                    <option value="furrow">Furrow Irrigation</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="calculateIrrigation()">
                <span class="material-icons">calculate</span> Calculate Water Needs
            </button>
        </div>
        <div class="irrigation-results" id="irrigationResults">
            <div class="irrigation-stat">
                <span class="material-icons">opacity</span>
                <div>
                    <strong id="waterNeeded">--</strong>
                    <small>Liters Needed Today</small>
                </div>
            </div>
            <div class="irrigation-stat">
                <span class="material-icons">schedule</span>
                <div>
                    <strong id="irrigationDuration">--</strong>
                    <small>Recommended Duration</small>
                </div>
            </div>
            <div class="irrigation-stat">
                <span class="material-icons">event</span>
                <div>
                    <strong id="nextIrrigation">--</strong>
                    <small>Next Irrigation</small>
                </div>
            </div>
            <div class="irrigation-stat">
                <span class="material-icons">savings</span>
                <div>
                    <strong id="waterSavings">--</strong>
                    <small>Potential Savings</small>
                </div>
            </div>
        </div>
        <div class="irrigation-schedule" id="irrigationSchedule">
            <h4><span class="material-icons">calendar_month</span> 7-Day Irrigation Schedule</h4>
            <div class="schedule-grid" id="scheduleGrid">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Monsoon & Season Tracker -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #1abc9c;">thunderstorm</span>
            Monsoon & Season Tracker
            <span class="badge badge-info">Bangladesh</span>
        </h3>
    </div>
    <div class="monsoon-container">
        <div class="season-timeline">
            <div class="timeline-bar">
                <div class="season-segment pre-monsoon" style="width: 16.67%;">
                    <span>Pre-Monsoon</span>
                    <small>Mar-May</small>
                </div>
                <div class="season-segment monsoon" style="width: 33.33%;">
                    <span>Monsoon</span>
                    <small>Jun-Sep</small>
                </div>
                <div class="season-segment post-monsoon" style="width: 16.67%;">
                    <span>Post-Monsoon</span>
                    <small>Oct-Nov</small>
                </div>
                <div class="season-segment winter" style="width: 16.67%;">
                    <span>Winter</span>
                    <small>Dec-Feb</small>
                </div>
                <div class="current-marker" id="seasonMarker"></div>
            </div>
        </div>
        <div class="monsoon-stats">
            <div class="monsoon-stat-card">
                <span class="material-icons">water_drop</span>
                <div>
                    <strong id="seasonRainfall">-- mm</strong>
                    <small>Season Rainfall (Est.)</small>
                </div>
            </div>
            <div class="monsoon-stat-card">
                <span class="material-icons">event</span>
                <div>
                    <strong id="daysInSeason">--</strong>
                    <small>Days in Current Season</small>
                </div>
            </div>
            <div class="monsoon-stat-card">
                <span class="material-icons">trending_up</span>
                <div>
                    <strong id="seasonProgress">--%</strong>
                    <small>Season Progress</small>
                </div>
            </div>
            <div class="monsoon-stat-card">
                <span class="material-icons">upcoming</span>
                <div>
                    <strong id="nextSeasonDays">--</strong>
                    <small>Days to Next Season</small>
                </div>
            </div>
        </div>
        <div class="season-info" id="seasonInfo">
            <div class="current-season-card">
                <h4><span class="material-icons">eco</span> Current Season: <span id="currentSeasonName">--</span></h4>
                <p id="seasonDescription">Loading seasonal information...</p>
                <div class="season-crops">
                    <strong>Recommended Crops:</strong>
                    <div class="crop-tags" id="seasonCropTags">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Voice Weather Report -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #e67e22;">record_voice_over</span>
            Voice Weather Report
        </h3>
    </div>
    <div class="voice-report-container">
        <div class="voice-controls">
            <button class="voice-btn play-btn" onclick="playWeatherReport()" id="playVoiceBtn">
                <span class="material-icons">play_arrow</span>
            </button>
            <button class="voice-btn" onclick="pauseWeatherReport()" id="pauseVoiceBtn" disabled>
                <span class="material-icons">pause</span>
            </button>
            <button class="voice-btn" onclick="stopWeatherReport()" id="stopVoiceBtn" disabled>
                <span class="material-icons">stop</span>
            </button>
        </div>
        <div class="voice-settings">
            <div class="voice-setting">
                <label>Language</label>
                <select id="voiceLanguage" onchange="updateVoiceSettings()">
                    <option value="en-US">English</option>
                    <option value="bn-BD">বাংলা (Bengali)</option>
                    <option value="hi-IN">हिंदी (Hindi)</option>
                </select>
            </div>
            <div class="voice-setting">
                <label>Speed</label>
                <input type="range" id="voiceSpeed" min="0.5" max="2" step="0.1" value="1" onchange="updateVoiceSettings()">
                <span id="speedValue">1x</span>
            </div>
        </div>
        <div class="voice-transcript" id="voiceTranscript">
            <p>Click play to hear the current weather report for your region.</p>
        </div>
    </div>
</div>

<!-- Weather Accuracy & Feedback -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #f39c12;">rate_review</span>
            Weather Accuracy Feedback
        </h3>
    </div>
    <div class="feedback-container">
        <p>Help us improve predictions! How accurate was yesterday's forecast?</p>
        <div class="accuracy-options">
            <button class="accuracy-btn" onclick="submitAccuracy('very-accurate')">
                <span class="material-icons">sentiment_very_satisfied</span>
                <span>Very Accurate</span>
            </button>
            <button class="accuracy-btn" onclick="submitAccuracy('accurate')">
                <span class="material-icons">sentiment_satisfied</span>
                <span>Accurate</span>
            </button>
            <button class="accuracy-btn" onclick="submitAccuracy('somewhat')">
                <span class="material-icons">sentiment_neutral</span>
                <span>Somewhat</span>
            </button>
            <button class="accuracy-btn" onclick="submitAccuracy('inaccurate')">
                <span class="material-icons">sentiment_dissatisfied</span>
                <span>Inaccurate</span>
            </button>
        </div>
        <div class="feedback-stats">
            <p><small>📊 Based on <strong id="totalFeedback">124</strong> farmer feedbacks, our accuracy is <strong id="accuracyRate">87%</strong></small></p>
        </div>
    </div>
</div>

<!-- Sun & Moon Information -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #f1c40f;">wb_twilight</span>
            Sun & Moon Information
        </h3>
    </div>
    <div class="sun-moon-container">
        <div class="sun-moon-grid">
            <div class="sun-card">
                <div class="sun-visual">
                    <div class="sun-arc" id="sunArc">
                        <div class="sun-position" id="sunPosition"></div>
                    </div>
                    <div class="horizon-line"></div>
                </div>
                <div class="sun-times">
                    <div class="sun-time">
                        <span class="material-icons">wb_sunny</span>
                        <div>
                            <strong id="sunriseTime">--:--</strong>
                            <small>Sunrise</small>
                        </div>
                    </div>
                    <div class="sun-time">
                        <span class="material-icons" style="color: #e67e22;">nights_stay</span>
                        <div>
                            <strong id="sunsetTime">--:--</strong>
                            <small>Sunset</small>
                        </div>
                    </div>
                </div>
                <div class="daylight-info">
                    <span class="material-icons">schedule</span>
                    <strong id="daylightHours">-- hrs -- min</strong> of daylight
                </div>
            </div>
            <div class="moon-card">
                <div class="moon-visual" id="moonVisual">
                    <div class="moon-phase" id="moonPhaseIcon">🌕</div>
                </div>
                <div class="moon-info">
                    <h4 id="moonPhaseName">Full Moon</h4>
                    <p id="moonIllumination">Illumination: --%</p>
                    <div class="moon-times">
                        <span><span class="material-icons">arrow_upward</span> <span id="moonriseTime">--:--</span></span>
                        <span><span class="material-icons">arrow_downward</span> <span id="moonsetTime">--:--</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="golden-hour-info">
            <div class="golden-hour">
                <span class="material-icons" style="color: #f39c12;">camera</span>
                <div>
                    <strong>Golden Hour (Morning)</strong>
                    <span id="goldenHourMorning">--:-- - --:--</span>
                </div>
            </div>
            <div class="golden-hour">
                <span class="material-icons" style="color: #e74c3c;">camera</span>
                <div>
                    <strong>Golden Hour (Evening)</strong>
                    <span id="goldenHourEvening">--:-- - --:--</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Weather Comparison Tool -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #3498db;">compare_arrows</span>
            Weather Comparison Tool
        </h3>
    </div>
    <div class="comparison-container">
        <div class="comparison-selectors">
            <div class="comparison-select">
                <label>Region 1</label>
                <select id="compareRegion1" onchange="updateComparison()">
                    <?php foreach ($regions as $name => $coords): ?>
                    <option value="<?php echo $name; ?>" data-lat="<?php echo $coords['lat']; ?>" data-lon="<?php echo $coords['lon']; ?>">
                        <?php echo $name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="comparison-vs">
                <span class="material-icons">sync_alt</span>
            </div>
            <div class="comparison-select">
                <label>Region 2</label>
                <select id="compareRegion2" onchange="updateComparison()">
                    <?php foreach ($regions as $name => $coords): ?>
                    <option value="<?php echo $name; ?>" data-lat="<?php echo $coords['lat']; ?>" data-lon="<?php echo $coords['lon']; ?>" <?php echo $name === 'Chittagong' ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="comparison-results" id="comparisonResults">
            <div class="comparison-loading">
                <span class="material-icons spinning">sync</span>
                <p>Select regions to compare...</p>
            </div>
        </div>
    </div>
</div>

<!-- Planting Calendar -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #27ae60;">event_available</span>
            Smart Planting Calendar
            <span class="badge badge-success">AI Recommended</span>
        </h3>
    </div>
    <div class="planting-calendar-container">
        <div class="calendar-legend">
            <span class="legend-item"><span class="dot dot-excellent"></span> Excellent</span>
            <span class="legend-item"><span class="dot dot-good"></span> Good</span>
            <span class="legend-item"><span class="dot dot-fair"></span> Fair</span>
            <span class="legend-item"><span class="dot dot-poor"></span> Poor</span>
        </div>
        <div class="planting-days-grid" id="plantingCalendar">
            <!-- Will be populated by JavaScript -->
        </div>
        <div class="planting-activities">
            <h4><span class="material-icons">agriculture</span> Activity Recommendations</h4>
            <div class="activity-grid" id="activityGrid">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Historical Weather Analysis -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #9b59b6;">history</span>
            Historical Weather Analysis
        </h3>
        <div class="header-actions">
            <select id="historicalPeriod" onchange="loadHistoricalData()">
                <option value="7">Last 7 Days</option>
                <option value="14">Last 14 Days</option>
                <option value="30" selected>Last 30 Days</option>
            </select>
        </div>
    </div>
    <div class="historical-container">
        <div class="historical-stats" id="historicalStats">
            <div class="hist-stat">
                <span class="material-icons">thermostat</span>
                <div class="hist-stat-info">
                    <span class="hist-value" id="histAvgTemp">--°C</span>
                    <span class="hist-label">Avg Temperature</span>
                </div>
            </div>
            <div class="hist-stat">
                <span class="material-icons">water_drop</span>
                <div class="hist-stat-info">
                    <span class="hist-value" id="histTotalRain">-- mm</span>
                    <span class="hist-label">Total Rainfall</span>
                </div>
            </div>
            <div class="hist-stat">
                <span class="material-icons">wb_sunny</span>
                <div class="hist-stat-info">
                    <span class="hist-value" id="histSunnyDays">--</span>
                    <span class="hist-label">Sunny Days</span>
                </div>
            </div>
            <div class="hist-stat">
                <span class="material-icons">cloud</span>
                <div class="hist-stat-info">
                    <span class="hist-value" id="histRainyDays">--</span>
                    <span class="hist-label">Rainy Days</span>
                </div>
            </div>
        </div>
        <div class="historical-chart">
            <canvas id="historicalChart"></canvas>
        </div>
    </div>
</div>

<!-- Weather Alerts Subscription -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #e74c3c;">notifications_active</span>
            Weather Alert Subscriptions
        </h3>
    </div>
    <div class="alert-subscription-container">
        <p class="subscription-desc">Get notified about important weather changes that may affect your farming activities.</p>
        <div class="alert-types-grid">
            <label class="alert-type-card">
                <input type="checkbox" id="alertRain" checked>
                <div class="alert-type-content">
                    <span class="material-icons">water_drop</span>
                    <span>Heavy Rain</span>
                </div>
            </label>
            <label class="alert-type-card">
                <input type="checkbox" id="alertStorm" checked>
                <div class="alert-type-content">
                    <span class="material-icons">thunderstorm</span>
                    <span>Storms</span>
                </div>
            </label>
            <label class="alert-type-card">
                <input type="checkbox" id="alertHeat">
                <div class="alert-type-content">
                    <span class="material-icons">whatshot</span>
                    <span>Heat Wave</span>
                </div>
            </label>
            <label class="alert-type-card">
                <input type="checkbox" id="alertFrost">
                <div class="alert-type-content">
                    <span class="material-icons">ac_unit</span>
                    <span>Frost</span>
                </div>
            </label>
            <label class="alert-type-card">
                <input type="checkbox" id="alertFlood" checked>
                <div class="alert-type-content">
                    <span class="material-icons">waves</span>
                    <span>Flood Risk</span>
                </div>
            </label>
            <label class="alert-type-card">
                <input type="checkbox" id="alertDrought">
                <div class="alert-type-content">
                    <span class="material-icons">wb_sunny</span>
                    <span>Drought</span>
                </div>
            </label>
        </div>
        <div class="notification-methods">
            <h4>Notification Methods</h4>
            <div class="method-options">
                <label class="method-option">
                    <input type="checkbox" id="notifyEmail" checked>
                    <span class="material-icons">email</span>
                    Email Alerts
                </label>
                <label class="method-option">
                    <input type="checkbox" id="notifySMS">
                    <span class="material-icons">sms</span>
                    SMS Alerts
                </label>
                <label class="method-option">
                    <input type="checkbox" id="notifyPush" checked>
                    <span class="material-icons">notifications</span>
                    Push Notifications
                </label>
            </div>
        </div>
        <button class="btn btn-primary btn-block mt-3" onclick="saveAlertPreferences()">
            <span class="material-icons">save</span> Save Preferences
        </button>
    </div>
</div>

<!-- Export & Share Section -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #2ecc71;">share</span>
            Export & Share Weather Report
        </h3>
    </div>
    <div class="export-container">
        <div class="export-options">
            <button class="export-btn" onclick="exportWeatherPDF()">
                <span class="material-icons">picture_as_pdf</span>
                <span>Export PDF</span>
            </button>
            <button class="export-btn" onclick="exportWeatherExcel()">
                <span class="material-icons">table_chart</span>
                <span>Export Excel</span>
            </button>
            <button class="export-btn" onclick="printWeatherReport()">
                <span class="material-icons">print</span>
                <span>Print Report</span>
            </button>
            <button class="export-btn" onclick="shareWeatherReport()">
                <span class="material-icons">share</span>
                <span>Share</span>
            </button>
        </div>
        <div class="share-links mt-3">
            <p><small>Share current weather:</small></p>
            <div class="social-share">
                <button class="social-btn whatsapp" onclick="shareToWhatsApp()">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/whatsapp.svg" alt="WhatsApp">
                </button>
                <button class="social-btn facebook" onclick="shareToFacebook()">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/facebook.svg" alt="Facebook">
                </button>
                <button class="social-btn twitter" onclick="shareToTwitter()">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/x.svg" alt="X">
                </button>
                <button class="social-btn copy" onclick="copyWeatherLink()">
                    <span class="material-icons">content_copy</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Weather Widget Code -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons" style="color: #1abc9c;">code</span>
            Embed Weather Widget
        </h3>
    </div>
    <div class="widget-container">
        <p>Add this weather widget to your website or dashboard:</p>
        <div class="widget-preview" id="widgetPreview">
            <div class="mini-weather-widget">
                <div class="widget-header">
                    <span class="material-icons">wb_cloudy</span>
                    <span id="widgetLocation">Dhaka</span>
                </div>
                <div class="widget-temp" id="widgetTemp">--°C</div>
                <div class="widget-condition" id="widgetCondition">Loading...</div>
            </div>
        </div>
        <div class="widget-code">
            <label>Embed Code:</label>
            <div class="code-block">
                <code id="embedCode">&lt;iframe src="<?php echo $base_url; ?>widget/weather?region=<?php echo $userRegion; ?>" width="300" height="150" frameborder="0"&gt;&lt;/iframe&gt;</code>
                <button class="btn btn-sm" onclick="copyEmbedCode()">
                    <span class="material-icons">content_copy</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Data Sources Footer -->
<div class="card mt-4 data-sources-card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">source</span>
            Data Sources & APIs
        </h3>
    </div>
    <div class="data-sources-grid">
        <div class="data-source">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e5/NASA_logo.svg/50px-NASA_logo.svg.png" alt="NASA" onerror="this.style.display='none'">
            <span>NASA EONET</span>
        </div>
        <div class="data-source">
            <span class="material-icons">public</span>
            <span>Open-Meteo</span>
        </div>
        <div class="data-source">
            <span class="material-icons">warning</span>
            <span>GDACS</span>
        </div>
        <div class="data-source">
            <span class="material-icons">satellite_alt</span>
            <span>RainViewer</span>
        </div>
        <div class="data-source">
            <span class="material-icons">psychology</span>
            <span>Groq AI</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const regions = <?php echo json_encode($regions); ?>;
const userCrops = <?php echo json_encode($userCrops); ?>;
const groqApiKey = '<?php echo $groqApiKey; ?>';

// API Configuration from PHP config
const apiConfig = <?php echo json_encode($apiConfig); ?>;

let currentLat = <?php echo $regions[$userRegion]['lat']; ?>;
let currentLon = <?php echo $regions[$userRegion]['lon']; ?>;
let currentRegion = '<?php echo $userRegion; ?>';
let weatherChart = null;
let currentWeatherData = null;

// Weather icon mapping for WMO codes (Open-Meteo)
const weatherCodes = {
    0: { icon: 'wb_sunny', desc: 'Clear sky' },
    1: { icon: 'wb_sunny', desc: 'Mainly clear' },
    2: { icon: 'partly_cloudy_day', desc: 'Partly cloudy' },
    3: { icon: 'cloud', desc: 'Overcast' },
    45: { icon: 'foggy', desc: 'Foggy' },
    48: { icon: 'foggy', desc: 'Depositing rime fog' },
    51: { icon: 'grain', desc: 'Light drizzle' },
    53: { icon: 'grain', desc: 'Moderate drizzle' },
    55: { icon: 'grain', desc: 'Dense drizzle' },
    61: { icon: 'water_drop', desc: 'Slight rain' },
    63: { icon: 'water_drop', desc: 'Moderate rain' },
    65: { icon: 'water_drop', desc: 'Heavy rain' },
    66: { icon: 'ac_unit', desc: 'Light freezing rain' },
    67: { icon: 'ac_unit', desc: 'Heavy freezing rain' },
    71: { icon: 'ac_unit', desc: 'Slight snowfall' },
    73: { icon: 'ac_unit', desc: 'Moderate snowfall' },
    75: { icon: 'ac_unit', desc: 'Heavy snowfall' },
    80: { icon: 'grain', desc: 'Slight rain showers' },
    81: { icon: 'water_drop', desc: 'Moderate rain showers' },
    82: { icon: 'water_drop', desc: 'Violent rain showers' },
    95: { icon: 'thunderstorm', desc: 'Thunderstorm' },
    96: { icon: 'thunderstorm', desc: 'Thunderstorm with hail' },
    99: { icon: 'thunderstorm', desc: 'Thunderstorm with heavy hail' }
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadWeatherData();
    loadNASADisasterData();
    loadSatelliteImagery('rainfall');
});

function changeRegion() {
    const select = document.getElementById('region');
    const option = select.options[select.selectedIndex];
    currentLat = parseFloat(option.dataset.lat);
    currentLon = parseFloat(option.dataset.lon);
    currentRegion = select.value;
    
    document.getElementById('locationLat').textContent = currentLat.toFixed(4);
    document.getElementById('locationLng').textContent = currentLon.toFixed(4);
    document.getElementById('locationInfo').style.display = 'block';
    document.getElementById('satelliteLocation').textContent = currentRegion;
    
    loadWeatherData();
    loadNASADisasterData();
    loadSatelliteImagery('rainfall');
}

function detectLocation() {
    if (!navigator.geolocation) {
        showNotification('Geolocation is not supported by your browser', 'error');
        return;
    }
    
    const btn = document.querySelector('.btn-detect-location');
    btn.innerHTML = '<span class="material-icons spinning">sync</span> Detecting...';
    btn.disabled = true;
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            currentLat = position.coords.latitude;
            currentLon = position.coords.longitude;
            
            document.getElementById('locationLat').textContent = currentLat.toFixed(4);
            document.getElementById('locationLng').textContent = currentLon.toFixed(4);
            document.getElementById('locationInfo').style.display = 'block';
            
            // Find nearest region
            let nearestRegion = currentRegion;
            let minDistance = Infinity;
            for (const [name, coords] of Object.entries(regions)) {
                const dist = Math.sqrt(Math.pow(coords.lat - currentLat, 2) + Math.pow(coords.lon - currentLon, 2));
                if (dist < minDistance) {
                    minDistance = dist;
                    nearestRegion = name;
                }
            }
            document.getElementById('region').value = nearestRegion;
            
            loadWeatherData();
            showNotification('Location detected successfully!', 'success');
            
            btn.innerHTML = '<span class="material-icons">my_location</span> Auto-Detect Location';
            btn.disabled = false;
        },
        function(error) {
            showNotification('Unable to detect location: ' + error.message, 'error');
            btn.innerHTML = '<span class="material-icons">my_location</span> Auto-Detect Location';
            btn.disabled = false;
        }
    );
}

function loadWeatherData() {
    // Show loading states
    document.getElementById('weatherData').innerHTML = '<div class="weather-loading"><span class="material-icons spinning">sync</span><p>Loading weather data...</p></div>';
    document.getElementById('forecastContainer').innerHTML = '<div class="forecast-loading"><span class="material-icons spinning">sync</span><p>Loading forecast...</p></div>';
    document.getElementById('hourlyContainer').innerHTML = '<div class="forecast-loading"><span class="material-icons spinning">sync</span><p>Loading...</p></div>';
    
    // Open-Meteo API with extended parameters - Free, no API key required
    const apiUrl = `${apiConfig.openMeteo}?latitude=${currentLat}&longitude=${currentLon}&current=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m,surface_pressure,uv_index&hourly=temperature_2m,relative_humidity_2m,precipitation_probability,weather_code,uv_index,soil_moisture_0_to_1cm&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,uv_index_max,et0_fao_evapotranspiration,sunrise,sunset&timezone=auto`;
    
    fetch(apiUrl)
        .then(r => r.json())
        .then(data => {
            if (data.current) {
                currentWeatherData = data;
                displayCurrentWeather(data);
                displayForecast(data);
                displayHourlyForecast(data);
                updateChart(data);
                updateQuickStats(data);
                updateAgriIndices(data);
                updateCropRecommendations(data);
                document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
                
                // Auto-generate AI summary
                generateAISummary(data);
                
                // New features
                updateSunMoonInfo(data);
                updatePlantingCalendar(data);
                updateWidgetPreview();
                
                // Advanced professional features
                updateAdvancedMetrics(data);
                updatePestRisk(data);
            } else {
                throw new Error('Invalid response');
            }
        })
        .catch(err => {
            console.error('Weather API error:', err);
            showNotification('Failed to load weather data. Please try again.', 'error');
            document.getElementById('weatherData').innerHTML = '<div class="weather-loading"><span class="material-icons">cloud_off</span><p>Unable to load weather data</p><button class="btn btn-sm" onclick="loadWeatherData()">Retry</button></div>';
        });
    
    // Also fetch air quality data
    fetchAirQuality();
}

// Fetch Air Quality from Open-Meteo
function fetchAirQuality() {
    fetch(`${apiConfig.openMeteoAirQuality}?latitude=${currentLat}&longitude=${currentLon}&current=pm10,pm2_5,us_aqi`)
        .then(r => r.json())
        .then(data => {
            if (data.current) {
                const aqi = data.current.us_aqi || 0;
                let aqiStatus = 'Good';
                let aqiColor = '#27ae60';
                
                if (aqi > 150) { aqiStatus = 'Unhealthy'; aqiColor = '#e74c3c'; }
                else if (aqi > 100) { aqiStatus = 'Moderate'; aqiColor = '#f39c12'; }
                else if (aqi > 50) { aqiStatus = 'Fair'; aqiColor = '#3498db'; }
                
                document.getElementById('statAQI').innerHTML = `
                    <span class="material-icons" style="color: ${aqiColor}">air</span>
                    <div class="stat-value" style="color: ${aqiColor}">${aqi}</div>
                    <div class="stat-label">${aqiStatus}</div>
                `;
            }
        })
        .catch(() => {});
}

// Update Quick Stats
function updateQuickStats(data) {
    const current = data.current;
    document.getElementById('statTemp').innerHTML = `
        <span class="material-icons">thermostat</span>
        <div class="stat-value">${Math.round(current.temperature_2m)}°C</div>
        <div class="stat-label">Temperature</div>
    `;
    document.getElementById('statHumidity').innerHTML = `
        <span class="material-icons">water_drop</span>
        <div class="stat-value">${current.relative_humidity_2m}%</div>
        <div class="stat-label">Humidity</div>
    `;
    document.getElementById('statUV').innerHTML = `
        <span class="material-icons" style="color: ${getUVColor(current.uv_index || 0)}">wb_sunny</span>
        <div class="stat-value">${(current.uv_index || 0).toFixed(1)}</div>
        <div class="stat-label">${getUVLevel(current.uv_index || 0)}</div>
    `;
    document.getElementById('statRain').innerHTML = `
        <span class="material-icons">grain</span>
        <div class="stat-value">${current.precipitation.toFixed(1)} mm</div>
        <div class="stat-label">Precipitation</div>
    `;
    document.getElementById('statWind').innerHTML = `
        <span class="material-icons">air</span>
        <div class="stat-value">${current.wind_speed_10m.toFixed(1)} km/h</div>
        <div class="stat-label">Wind Speed</div>
    `;
}

function getUVColor(uv) {
    if (uv >= 11) return '#8e44ad';
    if (uv >= 8) return '#e74c3c';
    if (uv >= 6) return '#e67e22';
    if (uv >= 3) return '#f1c40f';
    return '#27ae60';
}

function getUVLevel(uv) {
    if (uv >= 11) return 'Extreme';
    if (uv >= 8) return 'Very High';
    if (uv >= 6) return 'High';
    if (uv >= 3) return 'Moderate';
    return 'Low';
}

// Update Agricultural Indices
function updateAgriIndices(data) {
    const daily = data.daily;
    const hourly = data.hourly;
    
    // Growing Degree Days (GDD) - simplified calculation
    const avgTemp = (daily.temperature_2m_max[0] + daily.temperature_2m_min[0]) / 2;
    const baseTemp = 10; // Base temperature for most crops
    const gdd = Math.max(0, avgTemp - baseTemp);
    
    document.getElementById('gddIndex').innerHTML = `
        <div class="index-icon"><span class="material-icons">thermostat</span></div>
        <div class="index-value">${gdd.toFixed(1)}</div>
        <div class="index-label">Growing Degree Days</div>
        <div class="index-status ${gdd > 15 ? 'status-good' : gdd > 5 ? 'status-moderate' : 'status-low'}">${gdd > 15 ? 'Excellent Growth' : gdd > 5 ? 'Moderate Growth' : 'Slow Growth'}</div>
    `;
    
    // Evapotranspiration
    const et = daily.et0_fao_evapotranspiration ? daily.et0_fao_evapotranspiration[0] : 0;
    document.getElementById('etIndex').innerHTML = `
        <div class="index-icon"><span class="material-icons">opacity</span></div>
        <div class="index-value">${et.toFixed(1)} mm</div>
        <div class="index-label">Evapotranspiration</div>
        <div class="index-status ${et > 5 ? 'status-high' : 'status-moderate'}">${et > 5 ? 'High Water Loss' : 'Normal'}</div>
    `;
    
    // Soil Moisture (from hourly data)
    const soilMoisture = hourly.soil_moisture_0_to_1cm ? (hourly.soil_moisture_0_to_1cm[0] * 100) : 30;
    document.getElementById('soilMoistureIndex').innerHTML = `
        <div class="index-icon"><span class="material-icons">grass</span></div>
        <div class="index-value">${soilMoisture.toFixed(0)}%</div>
        <div class="index-label">Soil Moisture</div>
        <div class="index-status ${soilMoisture > 60 ? 'status-good' : soilMoisture > 30 ? 'status-moderate' : 'status-low'}">${soilMoisture > 60 ? 'Adequate' : soilMoisture > 30 ? 'Moderate' : 'Needs Irrigation'}</div>
    `;
    
    // Frost Risk
    const minTemp = daily.temperature_2m_min[0];
    let frostRisk = 'None';
    let frostClass = 'status-good';
    if (minTemp <= 0) { frostRisk = 'High'; frostClass = 'status-high'; }
    else if (minTemp <= 4) { frostRisk = 'Moderate'; frostClass = 'status-moderate'; }
    else if (minTemp <= 8) { frostRisk = 'Low'; frostClass = 'status-low'; }
    
    document.getElementById('frostRiskIndex').innerHTML = `
        <div class="index-icon"><span class="material-icons">ac_unit</span></div>
        <div class="index-value">${frostRisk}</div>
        <div class="index-label">Frost Risk</div>
        <div class="index-status ${frostClass}">Min: ${minTemp.toFixed(1)}°C</div>
    `;
}

// NASA EONET Natural Events API
function loadNASADisasterData() {
    const container = document.getElementById('disasterAlertsContainer');
    container.innerHTML = '<div class="forecast-loading"><span class="material-icons spinning">sync</span><p>Fetching disaster data...</p></div>';
    
    // Fetch NASA EONET data (free, no API key)
    fetch(`${apiConfig.nasaEonet}?status=open&limit=20`)
        .then(r => r.json())
        .then(data => {
            displayDisasterAlerts(data.events || []);
        })
        .catch(err => {
            console.error('NASA EONET error:', err);
            container.innerHTML = '<div class="notice notice-info"><p>Unable to fetch NASA disaster data. Will retry later.</p></div>';
        });
}

function displayDisasterAlerts(events) {
    const container = document.getElementById('disasterAlertsContainer');
    
    // Filter events near Bangladesh (within ~2000km)
    const nearbyEvents = events.filter(event => {
        if (!event.geometry || !event.geometry[0] || !event.geometry[0].coordinates) return false;
        const coords = event.geometry[0].coordinates;
        const eventLat = coords[1];
        const eventLon = coords[0];
        const distance = getDistance(currentLat, currentLon, eventLat, eventLon);
        return distance < 2000; // Within 2000km
    });
    
    // Also include all severe events globally
    const severeEvents = events.filter(event => 
        event.categories && event.categories.some(c => 
            ['severeStorms', 'floods', 'earthquakes', 'volcanoes', 'wildfires'].includes(c.id)
        )
    ).slice(0, 5);
    
    const allEvents = [...new Map([...nearbyEvents, ...severeEvents].map(e => [e.id, e])).values()].slice(0, 8);
    
    if (allEvents.length === 0) {
        container.innerHTML = `
            <div class="notice notice-success">
                <div class="alert-content">
                    <div class="alert-header">
                        <span class="material-icons">verified_user</span>
                        <strong>No Nearby Natural Disasters</strong>
                    </div>
                    <p>No significant natural events detected in your region from NASA satellites.</p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '<div class="disaster-events-grid">';
    
    allEvents.forEach(event => {
        const category = event.categories[0];
        const icon = getDisasterIcon(category.id);
        const color = getDisasterColor(category.id);
        const coords = event.geometry[0]?.coordinates;
        const distance = coords ? getDistance(currentLat, currentLon, coords[1], coords[0]) : null;
        
        html += `
            <div class="disaster-event-card" style="border-left-color: ${color}">
                <div class="disaster-icon" style="background: ${color}20; color: ${color}">
                    <span class="material-icons">${icon}</span>
                </div>
                <div class="disaster-info">
                    <h5>${event.title}</h5>
                    <p class="disaster-category">${category.title}</p>
                    ${distance ? `<p class="disaster-distance"><span class="material-icons">place</span> ${distance.toFixed(0)} km away</p>` : ''}
                    <p class="disaster-date"><span class="material-icons">schedule</span> ${new Date(event.geometry[0]?.date || event.closed).toLocaleDateString()}</p>
                </div>
                <a href="${apiConfig.nasaEonet}/${event.id}" target="_blank" class="disaster-link">
                    <span class="material-icons">open_in_new</span>
                </a>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function getDisasterIcon(categoryId) {
    const icons = {
        'wildfires': 'local_fire_department',
        'severeStorms': 'thunderstorm',
        'floods': 'waves',
        'earthquakes': 'vibration',
        'volcanoes': 'volcano',
        'landslides': 'landscape',
        'snow': 'ac_unit',
        'seaLakeIce': 'water',
        'drought': 'wb_sunny',
        'dustHaze': 'blur_on',
        'tempExtremes': 'thermostat'
    };
    return icons[categoryId] || 'warning';
}

function getDisasterColor(categoryId) {
    const colors = {
        'wildfires': '#e74c3c',
        'severeStorms': '#9b59b6',
        'floods': '#3498db',
        'earthquakes': '#e67e22',
        'volcanoes': '#c0392b',
        'landslides': '#795548',
        'snow': '#00bcd4',
        'drought': '#f39c12'
    };
    return colors[categoryId] || '#7f8c8d';
}

function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// Satellite Imagery
function showSatelliteTab(type) {
    document.querySelectorAll('.satellite-tab').forEach(tab => tab.classList.remove('active'));
    event.target.closest('.satellite-tab').classList.add('active');
    loadSatelliteImagery(type);
}

function loadSatelliteImagery(type) {
    const container = document.getElementById('satelliteImageContainer');
    container.innerHTML = '<div class="satellite-loading"><span class="material-icons spinning">satellite_alt</span><p>Loading satellite imagery...</p></div>';
    
    let imageUrl = '';
    const timestamp = Date.now();
    
    switch(type) {
        case 'rainfall':
            // RainViewer API for rainfall radar
            fetch(apiConfig.rainviewer)
                .then(r => r.json())
                .then(data => {
                    const latestFrame = data.radar.past[data.radar.past.length - 1];
                    const radarUrl = `https://tilecache.rainviewer.com${latestFrame.path}/256/6/${Math.floor(currentLat)}/${Math.floor(currentLon)}/1/1_1.png`;
                    container.innerHTML = `
                        <div class="satellite-map-wrapper">
                            <iframe 
                                src="https://www.rainviewer.com/map.html?loc=${currentLat},${currentLon},7&oFa=0&oC=0&oU=0&oCS=1&oF=0&oAP=1&c=3&o=83&lm=1&layer=radar&sm=1&sn=1" 
                                width="100%" 
                                height="400" 
                                frameborder="0" 
                                style="border-radius: 8px;"
                                allowfullscreen>
                            </iframe>
                        </div>
                    `;
                })
                .catch(() => {
                    container.innerHTML = `
                        <div class="satellite-map-wrapper">
                            <iframe 
                                src="${apiConfig.windyEmbed}?lat=${currentLat}&lon=${currentLon}&detailLat=${currentLat}&detailLon=${currentLon}&width=650&height=400&zoom=6&level=surface&overlay=rain&product=ecmwf&menu=&message=&marker=&calendar=now&pressure=&type=map&location=coordinates&detail=&metricWind=km%2Fh&metricTemp=%C2%B0C&radarRange=-1" 
                                width="100%" 
                                height="400" 
                                frameborder="0"
                                style="border-radius: 8px;">
                            </iframe>
                        </div>
                    `;
                });
            break;
            
        case 'clouds':
            container.innerHTML = `
                <div class="satellite-map-wrapper">
                    <iframe 
                        src="${apiConfig.windyEmbed}?lat=${currentLat}&lon=${currentLon}&detailLat=${currentLat}&detailLon=${currentLon}&width=650&height=400&zoom=6&level=surface&overlay=clouds&product=ecmwf&menu=&message=&marker=&calendar=now&pressure=&type=map&location=coordinates&detail=&metricWind=km%2Fh&metricTemp=%C2%B0C" 
                        width="100%" 
                        height="400" 
                        frameborder="0"
                        style="border-radius: 8px;">
                    </iframe>
                </div>
            `;
            break;
            
        case 'vegetation':
            // NDVI from NASA GIBS
            container.innerHTML = `
                <div class="satellite-map-wrapper">
                    <img src="${apiConfig.nasaGibs}?SERVICE=WMS&REQUEST=GetMap&VERSION=1.3.0&LAYERS=MODIS_Terra_NDVI_8Day&CRS=EPSG:4326&BBOX=${currentLat-5},${currentLon-5},${currentLat+5},${currentLon+5}&WIDTH=600&HEIGHT=400&FORMAT=image/png&TIME=2024-12-01" 
                         alt="NDVI Vegetation Index" 
                         style="width: 100%; border-radius: 8px;"
                         onerror="this.parentElement.innerHTML='<div class=\'satellite-error\'><span class=\'material-icons\'>satellite_alt</span><p>NDVI imagery temporarily unavailable</p></div>'">
                    <div class="vegetation-legend">
                        <span class="legend-item"><span style="background: #8B4513;"></span> Bare Soil</span>
                        <span class="legend-item"><span style="background: #FFFF00;"></span> Sparse</span>
                        <span class="legend-item"><span style="background: #90EE90;"></span> Moderate</span>
                        <span class="legend-item"><span style="background: #228B22;"></span> Dense</span>
                    </div>
                </div>
            `;
            break;
            
        case 'temperature':
            container.innerHTML = `
                <div class="satellite-map-wrapper">
                    <iframe 
                        src="${apiConfig.windyEmbed}?lat=${currentLat}&lon=${currentLon}&detailLat=${currentLat}&detailLon=${currentLon}&width=650&height=400&zoom=6&level=surface&overlay=temp&product=ecmwf&menu=&message=&marker=&calendar=now&pressure=&type=map&location=coordinates&detail=&metricWind=km%2Fh&metricTemp=%C2%B0C" 
                        width="100%" 
                        height="400" 
                        frameborder="0"
                        style="border-radius: 8px;">
                    </iframe>
                </div>
            `;
            break;
    }
}

// AI-Powered Recommendations
function generateAISummary(weatherData) {
    const container = document.getElementById('aiSummaryContainer');
    
    if (!groqApiKey) {
        // Generate local AI-like summary without API
        const summary = generateLocalSummary(weatherData);
        container.innerHTML = summary;
        return;
    }
    
    const current = weatherData.current;
    const daily = weatherData.daily;
    
    const prompt = `You are an agricultural weather advisor for Bangladesh. Based on this weather data, provide a brief 2-3 sentence farming advisory:
    
Current: ${current.temperature_2m}°C, Humidity: ${current.relative_humidity_2m}%, Wind: ${current.wind_speed_10m}km/h, Rain: ${current.precipitation}mm
Forecast: Max ${daily.temperature_2m_max[0]}°C, Min ${daily.temperature_2m_min[0]}°C, Rain probability: ${daily.precipitation_probability_max[0]}%
User's crops: ${userCrops.length > 0 ? userCrops.join(', ') : 'General farming'}
Region: ${currentRegion}

Be specific about what farmers should do today. Keep response under 100 words.`;

    fetch(apiConfig.groqApi, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${groqApiKey}`
        },
        body: JSON.stringify({
            model: apiConfig.groqModel,
            messages: [{ role: 'user', content: prompt }],
            max_tokens: 150,
            temperature: 0.7
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.choices && data.choices[0]) {
            container.innerHTML = `
                <div class="ai-summary">
                    <div class="ai-header">
                        <span class="material-icons">psychology</span>
                        <strong>AI Weather Advisory</strong>
                        <span class="badge badge-ai">Powered by Groq AI</span>
                    </div>
                    <p>${data.choices[0].message.content}</p>
                </div>
            `;
        } else {
            container.innerHTML = generateLocalSummary(weatherData);
        }
    })
    .catch(() => {
        container.innerHTML = generateLocalSummary(weatherData);
    });
}

function generateLocalSummary(weatherData) {
    const current = weatherData.current;
    const daily = weatherData.daily;
    const weatherInfo = weatherCodes[current.weather_code] || { desc: 'Variable' };
    
    let summary = '';
    const temp = current.temperature_2m;
    const humidity = current.relative_humidity_2m;
    const rain = current.precipitation;
    const rainProb = daily.precipitation_probability_max[0];
    
    // Generate intelligent summary
    if (rainProb > 70 || rain > 5) {
        summary = `⚠️ <strong>Rain Alert:</strong> High precipitation expected (${rainProb}% probability). Postpone spraying and harvesting. Ensure proper drainage in fields. `;
    } else if (temp > 35) {
        summary = `🌡️ <strong>Heat Advisory:</strong> High temperatures (${temp}°C) may stress crops. Increase irrigation frequency and consider shade for sensitive plants. `;
    } else if (humidity > 85) {
        summary = `💧 <strong>High Humidity:</strong> Conditions favor fungal diseases. Monitor crops closely and ensure good air circulation. `;
    } else if (temp >= 20 && temp <= 30 && humidity < 70 && rain < 2) {
        summary = `✅ <strong>Ideal Conditions:</strong> Weather is favorable for most farming activities including planting, fertilizing, and harvesting. `;
    } else {
        summary = `📊 <strong>Moderate Conditions:</strong> Current ${weatherInfo.desc.toLowerCase()} weather with ${temp}°C temperature. Suitable for general farm maintenance. `;
    }
    
    // Add crop-specific advice if available
    if (userCrops.length > 0) {
        summary += `<br><span class="material-icons" style="font-size:14px">eco</span> <em>Monitor your ${userCrops.slice(0, 3).join(', ')} crops based on these conditions.</em>`;
    }
    
    return `
        <div class="ai-summary">
            <div class="ai-header">
                <span class="material-icons">lightbulb</span>
                <strong>Smart Weather Advisory</strong>
                <span class="badge badge-info">Auto-Generated</span>
            </div>
            <p>${summary}</p>
        </div>
    `;
}

function getAIRecommendations() {
    const btn = document.getElementById('aiRefreshBtn');
    btn.innerHTML = '<span class="material-icons spinning">sync</span> Analyzing...';
    btn.disabled = true;
    
    if (currentWeatherData) {
        generateAISummary(currentWeatherData);
    }
    
    setTimeout(() => {
        btn.innerHTML = '<span class="material-icons">auto_awesome</span> Get AI Insights';
        btn.disabled = false;
    }, 2000);
}

// Crop-Specific Recommendations
function updateCropRecommendations(weatherData) {
    const container = document.getElementById('cropRecommendationsContainer');
    const current = weatherData.current;
    const daily = weatherData.daily;
    
    // Common crops in Bangladesh
    const crops = userCrops.length > 0 ? userCrops : ['Rice', 'Wheat', 'Jute', 'Sugarcane', 'Potato', 'Vegetables'];
    
    const cropData = {
        'Rice': { optTemp: [20, 35], optHumidity: [70, 90], icon: 'grass', sensitive: 'flooding' },
        'Wheat': { optTemp: [15, 25], optHumidity: [50, 70], icon: 'grain', sensitive: 'heat' },
        'Jute': { optTemp: [24, 35], optHumidity: [70, 90], icon: 'park', sensitive: 'cold' },
        'Sugarcane': { optTemp: [20, 35], optHumidity: [60, 80], icon: 'yard', sensitive: 'frost' },
        'Potato': { optTemp: [15, 25], optHumidity: [60, 80], icon: 'set_meal', sensitive: 'heat' },
        'Vegetables': { optTemp: [18, 30], optHumidity: [50, 70], icon: 'eco', sensitive: 'extreme' },
        'Maize': { optTemp: [18, 32], optHumidity: [50, 80], icon: 'grass', sensitive: 'drought' },
        'Tomato': { optTemp: [20, 30], optHumidity: [50, 70], icon: 'eco', sensitive: 'humidity' }
    };
    
    let html = '<div class="crop-cards-grid">';
    
    crops.slice(0, 6).forEach(cropName => {
        const crop = cropData[cropName] || { optTemp: [20, 30], optHumidity: [50, 70], icon: 'eco', sensitive: 'extreme' };
        const temp = current.temperature_2m;
        const humidity = current.relative_humidity_2m;
        
        let status = 'optimal';
        let statusText = 'Optimal';
        let advice = 'Conditions are favorable.';
        
        if (temp < crop.optTemp[0]) {
            status = 'caution';
            statusText = 'Too Cold';
            advice = `Temperature (${temp}°C) is below optimal. Consider protective measures.`;
        } else if (temp > crop.optTemp[1]) {
            status = 'warning';
            statusText = 'Too Hot';
            advice = `Temperature (${temp}°C) is above optimal. Increase irrigation.`;
        } else if (humidity < crop.optHumidity[0]) {
            status = 'caution';
            statusText = 'Low Humidity';
            advice = `Humidity (${humidity}%) is low. Monitor soil moisture.`;
        } else if (humidity > crop.optHumidity[1]) {
            status = 'caution';
            statusText = 'High Humidity';
            advice = `Humidity (${humidity}%) is high. Watch for fungal diseases.`;
        }
        
        html += `
            <div class="crop-card crop-${status}">
                <div class="crop-header">
                    <span class="material-icons">${crop.icon}</span>
                    <h5>${cropName}</h5>
                </div>
                <div class="crop-status">
                    <span class="status-indicator status-${status}"></span>
                    ${statusText}
                </div>
                <p class="crop-advice">${advice}</p>
                <div class="crop-ranges">
                    <small>Optimal: ${crop.optTemp[0]}-${crop.optTemp[1]}°C, ${crop.optHumidity[0]}-${crop.optHumidity[1]}% RH</small>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function displayCurrentWeather(data) {
    const current = data.current;
    const weatherInfo = weatherCodes[current.weather_code] || { icon: 'wb_cloudy', desc: 'Unknown' };
    
    document.getElementById('weatherData').innerHTML = `
        <div class="current-weather-display">
            <div class="weather-main">
                <div class="weather-icon-large">
                    <span class="material-icons">${weatherInfo.icon}</span>
                </div>
                <div class="weather-temp-main">
                    <h2 class="weather-temp">${Math.round(current.temperature_2m)}°C</h2>
                    <p class="weather-feels">Feels like ${Math.round(current.apparent_temperature)}°C</p>
                </div>
            </div>
            <p class="weather-condition">${weatherInfo.desc}</p>
            <p class="weather-location"><span class="material-icons">location_on</span> ${currentRegion}, Bangladesh</p>
            
            <div class="weather-details-grid">
                <div class="weather-detail-item">
                    <span class="material-icons">water_drop</span>
                    <div>
                        <strong>${current.relative_humidity_2m}%</strong>
                        <small>Humidity</small>
                    </div>
                </div>
                <div class="weather-detail-item">
                    <span class="material-icons">air</span>
                    <div>
                        <strong>${current.wind_speed_10m.toFixed(1)} km/h</strong>
                        <small>Wind</small>
                    </div>
                </div>
                <div class="weather-detail-item">
                    <span class="material-icons">grain</span>
                    <div>
                        <strong>${current.precipitation.toFixed(1)} mm</strong>
                        <small>Rain</small>
                    </div>
                </div>
                <div class="weather-detail-item">
                    <span class="material-icons">speed</span>
                    <div>
                        <strong>${Math.round(current.surface_pressure)} hPa</strong>
                        <small>Pressure</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    updateRecommendations(current);
}

function displayForecast(data) {
    const daily = data.daily;
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    let html = '';
    for (let i = 0; i < Math.min(7, daily.time.length); i++) {
        const date = new Date(daily.time[i]);
        const dayName = i === 0 ? 'Today' : (i === 1 ? 'Tomorrow' : days[date.getDay()]);
        const weatherInfo = weatherCodes[daily.weather_code[i]] || { icon: 'wb_cloudy', desc: 'Unknown' };
        
        html += `
            <div class="forecast-card">
                <div class="forecast-day">${dayName}</div>
                <div class="forecast-date">${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</div>
                <div class="forecast-icon"><span class="material-icons">${weatherInfo.icon}</span></div>
                <div class="forecast-condition">${weatherInfo.desc}</div>
                <div class="forecast-temps">
                    <span class="temp-high">${Math.round(daily.temperature_2m_max[i])}°</span>
                    <span class="temp-low">${Math.round(daily.temperature_2m_min[i])}°</span>
                </div>
                <div class="forecast-details">
                    <span><span class="material-icons">water_drop</span>${daily.precipitation_probability_max[i]}%</span>
                    <span><span class="material-icons">grain</span>${daily.precipitation_sum[i].toFixed(1)}mm</span>
                </div>
            </div>
        `;
    }
    
    document.getElementById('forecastContainer').innerHTML = html;
}

function displayHourlyForecast(data) {
    const hourly = data.hourly;
    const currentHour = new Date().getHours();
    
    // Find the index for current hour
    const todayStr = new Date().toISOString().split('T')[0];
    let startIdx = hourly.time.findIndex(t => t.startsWith(todayStr) && new Date(t).getHours() >= currentHour);
    if (startIdx === -1) startIdx = 0;
    
    let html = '';
    for (let i = startIdx; i < Math.min(startIdx + 24, hourly.time.length); i++) {
        const time = new Date(hourly.time[i]);
        const weatherInfo = weatherCodes[hourly.weather_code[i]] || { icon: 'wb_cloudy', desc: 'Unknown' };
        const isNow = i === startIdx;
        
        html += `
            <div class="hourly-item ${isNow ? 'hourly-now' : ''}">
                <div class="hourly-time">${isNow ? 'Now' : time.getHours() + ':00'}</div>
                <div class="hourly-icon"><span class="material-icons">${weatherInfo.icon}</span></div>
                <div class="hourly-temp">${Math.round(hourly.temperature_2m[i])}°</div>
                <div class="hourly-rain"><span class="material-icons">water_drop</span>${hourly.precipitation_probability[i]}%</div>
            </div>
        `;
    }
    
    document.getElementById('hourlyContainer').innerHTML = html;
}

function updateRecommendations(current) {
    const temp = current.temperature_2m;
    const humidity = current.relative_humidity_2m;
    const rain = current.precipitation;
    const windSpeed = current.wind_speed_10m;
    const weatherCode = current.weather_code;
    
    const recommended = [];
    const notRecommended = [];
    const precautions = [];
    
    // Logic based on weather conditions
    if (rain < 2 && windSpeed < 20) {
        recommended.push('Spraying pesticides (favorable wind)');
    } else {
        notRecommended.push('Spraying pesticides (unfavorable conditions)');
    }
    
    if (humidity < 70 && rain < 5) {
        recommended.push('Light irrigation');
        recommended.push('Fertilizer application');
    } else {
        notRecommended.push('Heavy watering (high moisture)');
        notRecommended.push('Fertilizer application');
    }
    
    // Check for clear/sunny conditions (WMO codes 0-3)
    if (weatherCode <= 3 && rain < 2) {
        recommended.push('Harvesting (dry conditions)');
        recommended.push('Drying crops');
    } else {
        notRecommended.push('Harvesting (wet conditions)');
    }
    
    if (rain < 3) {
        recommended.push('Planting new seeds');
        recommended.push('Soil preparation');
    } else {
        notRecommended.push('Soil preparation (wet soil)');
    }
    
    // Precautions based on weather codes
    if (weatherCode >= 61 || rain > 5) {
        precautions.push('Monitor drainage systems');
        precautions.push('Protect crops from flooding');
        precautions.push('Avoid low-lying fields');
    }
    
    if (weatherCode >= 95) {
        precautions.push('Stay indoors during thunderstorms');
        precautions.push('Avoid metal structures');
    }
    
    if (windSpeed > 30) {
        precautions.push('Secure farm structures');
        precautions.push('Protect young plants');
    }
    
    if (temp > 35) {
        precautions.push('Increase irrigation frequency');
        precautions.push('Provide shade for sensitive crops');
    }
    
    if (humidity > 80) {
        precautions.push('Watch for fungal diseases');
        precautions.push('Ensure proper ventilation');
    }
    
    // Default precautions if none
    if (precautions.length === 0) {
        precautions.push('Regular crop monitoring');
        precautions.push('Check irrigation systems');
        precautions.push('Monitor pest activity');
    }
    
    // Render lists
    document.getElementById('recommendedList').innerHTML = recommended.length > 0 
        ? recommended.map(r => `<li><span class="material-icons">done</span> ${r}</li>`).join('')
        : '<li><span class="material-icons">info</span> No specific recommendations</li>';
    
    document.getElementById('notRecommendedList').innerHTML = notRecommended.length > 0
        ? notRecommended.map(r => `<li><span class="material-icons">close</span> ${r}</li>`).join('')
        : '<li><span class="material-icons">check</span> All activities suitable</li>';
    
    document.getElementById('precautionsList').innerHTML = precautions.map(p => `<li><span class="material-icons">shield</span> ${p}</li>`).join('');
}

function updateChart(data) {
    const daily = data.daily;
    const labels = [];
    const tempData = [];
    const humidityData = [];
    
    for (let i = 0; i < Math.min(7, daily.time.length); i++) {
        const date = new Date(daily.time[i]);
        labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
        tempData.push(Math.round((daily.temperature_2m_max[i] + daily.temperature_2m_min[i]) / 2));
        // Use precipitation probability as humidity indicator
        humidityData.push(daily.precipitation_probability_max[i]);
    }
    
    renderChart(labels, tempData, humidityData);
}

function renderChart(labels, tempData, humidityData) {
    const ctx = document.getElementById('tempChart').getContext('2d');
    
    if (weatherChart) {
        weatherChart.destroy();
    }
    
    weatherChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Temperature (°C)',
                data: tempData,
                borderColor: '#ff6b6b',
                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                tension: 0.3,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Humidity (%)',
                data: humidityData,
                borderColor: '#4dabf7',
                backgroundColor: 'rgba(77, 171, 247, 0.1)',
                tension: 0.3,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Temperature (°C)' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Humidity (%)' },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.innerHTML = `<span class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</span> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Auto-refresh every 10 minutes
setInterval(loadWeatherData, 600000);

// ===== SUN & MOON FUNCTIONS =====
function updateSunMoonInfo(data) {
    if (!data.daily) return;
    
    const sunrise = data.daily.sunrise ? data.daily.sunrise[0] : null;
    const sunset = data.daily.sunset ? data.daily.sunset[0] : null;
    
    if (sunrise && sunset) {
        const sunriseDate = new Date(sunrise);
        const sunsetDate = new Date(sunset);
        
        document.getElementById('sunriseTime').textContent = sunriseDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('sunsetTime').textContent = sunsetDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        // Calculate daylight hours
        const daylightMs = sunsetDate - sunriseDate;
        const daylightHours = Math.floor(daylightMs / (1000 * 60 * 60));
        const daylightMinutes = Math.floor((daylightMs % (1000 * 60 * 60)) / (1000 * 60));
        document.getElementById('daylightHours').textContent = `${daylightHours} hrs ${daylightMinutes} min`;
        
        // Golden hour calculations (approximately 1 hour after sunrise and 1 hour before sunset)
        const goldenMorningEnd = new Date(sunriseDate.getTime() + 60 * 60 * 1000);
        const goldenEveningStart = new Date(sunsetDate.getTime() - 60 * 60 * 1000);
        
        document.getElementById('goldenHourMorning').textContent = 
            `${sunriseDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })} - ${goldenMorningEnd.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`;
        document.getElementById('goldenHourEvening').textContent = 
            `${goldenEveningStart.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })} - ${sunsetDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`;
        
        // Update sun position
        updateSunPosition(sunriseDate, sunsetDate);
    }
    
    // Calculate moon phase
    updateMoonPhase();
}

function updateSunPosition(sunrise, sunset) {
    const now = new Date();
    const sunPosition = document.getElementById('sunPosition');
    
    if (now < sunrise || now > sunset) {
        // Night time
        sunPosition.style.display = 'none';
        return;
    }
    
    sunPosition.style.display = 'block';
    const totalDaylight = sunset - sunrise;
    const elapsed = now - sunrise;
    const progress = (elapsed / totalDaylight) * 100;
    
    // Calculate position on arc (0-180 degrees)
    const angle = (progress / 100) * 180;
    const radians = angle * (Math.PI / 180);
    const x = 50 + 40 * Math.cos(Math.PI - radians);
    const y = 50 - 35 * Math.sin(radians);
    
    sunPosition.style.left = `${x}%`;
    sunPosition.style.top = `${y}%`;
}

function updateMoonPhase() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1;
    const day = now.getDate();
    
    // Calculate moon phase (simplified algorithm)
    const c = Math.floor(365.25 * year);
    const e = Math.floor(30.6 * month);
    const jd = c + e + day - 694039.09;
    const phase = jd / 29.53058867;
    const phaseIndex = Math.floor((phase - Math.floor(phase)) * 8);
    
    const phases = [
        { icon: '🌑', name: 'New Moon', illumination: 0 },
        { icon: '🌒', name: 'Waxing Crescent', illumination: 12.5 },
        { icon: '🌓', name: 'First Quarter', illumination: 25 },
        { icon: '🌔', name: 'Waxing Gibbous', illumination: 37.5 },
        { icon: '🌕', name: 'Full Moon', illumination: 50 },
        { icon: '🌖', name: 'Waning Gibbous', illumination: 62.5 },
        { icon: '🌗', name: 'Last Quarter', illumination: 75 },
        { icon: '🌘', name: 'Waning Crescent', illumination: 87.5 }
    ];
    
    const currentPhase = phases[phaseIndex];
    const exactIllumination = Math.round(((phase - Math.floor(phase)) * 100));
    
    document.getElementById('moonPhaseIcon').textContent = currentPhase.icon;
    document.getElementById('moonPhaseName').textContent = currentPhase.name;
    document.getElementById('moonIllumination').textContent = `Illumination: ${Math.abs(50 - exactIllumination) * 2}%`;
    
    // Approximate moonrise/moonset (simplified)
    const moonriseHour = (6 + phaseIndex * 3) % 24;
    const moonsetHour = (18 + phaseIndex * 3) % 24;
    document.getElementById('moonriseTime').textContent = `${moonriseHour.toString().padStart(2, '0')}:00`;
    document.getElementById('moonsetTime').textContent = `${moonsetHour.toString().padStart(2, '0')}:00`;
}

// ===== WEATHER COMPARISON =====
async function updateComparison() {
    const select1 = document.getElementById('compareRegion1');
    const select2 = document.getElementById('compareRegion2');
    const container = document.getElementById('comparisonResults');
    
    const region1 = {
        name: select1.value,
        lat: select1.options[select1.selectedIndex].dataset.lat,
        lon: select1.options[select1.selectedIndex].dataset.lon
    };
    
    const region2 = {
        name: select2.value,
        lat: select2.options[select2.selectedIndex].dataset.lat,
        lon: select2.options[select2.selectedIndex].dataset.lon
    };
    
    container.innerHTML = '<div class="comparison-loading"><span class="material-icons spinning">sync</span><p>Comparing weather...</p></div>';
    
    try {
        const [data1, data2] = await Promise.all([
            fetch(`${apiConfig.openMeteo}?latitude=${region1.lat}&longitude=${region1.lon}&current=temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code&timezone=auto`).then(r => r.json()),
            fetch(`${apiConfig.openMeteo}?latitude=${region2.lat}&longitude=${region2.lon}&current=temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code&timezone=auto`).then(r => r.json())
        ]);
        
        const tempDiff = data1.current.temperature_2m - data2.current.temperature_2m;
        const humidityDiff = data1.current.relative_humidity_2m - data2.current.relative_humidity_2m;
        
        container.innerHTML = `
            <div class="comparison-table">
                <div class="comparison-row header">
                    <div class="comparison-metric">Metric</div>
                    <div class="comparison-value">${region1.name}</div>
                    <div class="comparison-diff">Difference</div>
                    <div class="comparison-value">${region2.name}</div>
                </div>
                <div class="comparison-row">
                    <div class="comparison-metric"><span class="material-icons">thermostat</span> Temperature</div>
                    <div class="comparison-value">${data1.current.temperature_2m}°C</div>
                    <div class="comparison-diff ${tempDiff > 0 ? 'diff-higher' : 'diff-lower'}">${tempDiff > 0 ? '+' : ''}${tempDiff.toFixed(1)}°C</div>
                    <div class="comparison-value">${data2.current.temperature_2m}°C</div>
                </div>
                <div class="comparison-row">
                    <div class="comparison-metric"><span class="material-icons">water_drop</span> Humidity</div>
                    <div class="comparison-value">${data1.current.relative_humidity_2m}%</div>
                    <div class="comparison-diff ${humidityDiff > 0 ? 'diff-higher' : 'diff-lower'}">${humidityDiff > 0 ? '+' : ''}${humidityDiff}%</div>
                    <div class="comparison-value">${data2.current.relative_humidity_2m}%</div>
                </div>
                <div class="comparison-row">
                    <div class="comparison-metric"><span class="material-icons">grain</span> Precipitation</div>
                    <div class="comparison-value">${data1.current.precipitation} mm</div>
                    <div class="comparison-diff">${(data1.current.precipitation - data2.current.precipitation).toFixed(1)} mm</div>
                    <div class="comparison-value">${data2.current.precipitation} mm</div>
                </div>
                <div class="comparison-row">
                    <div class="comparison-metric"><span class="material-icons">air</span> Wind Speed</div>
                    <div class="comparison-value">${data1.current.wind_speed_10m} km/h</div>
                    <div class="comparison-diff">${(data1.current.wind_speed_10m - data2.current.wind_speed_10m).toFixed(1)} km/h</div>
                    <div class="comparison-value">${data2.current.wind_speed_10m} km/h</div>
                </div>
                <div class="comparison-row">
                    <div class="comparison-metric"><span class="material-icons">wb_cloudy</span> Condition</div>
                    <div class="comparison-value">${weatherCodes[data1.current.weather_code]?.desc || 'Unknown'}</div>
                    <div class="comparison-diff">-</div>
                    <div class="comparison-value">${weatherCodes[data2.current.weather_code]?.desc || 'Unknown'}</div>
                </div>
            </div>
            <div class="comparison-summary">
                <p><span class="material-icons">lightbulb</span> <strong>Summary:</strong> 
                ${tempDiff > 2 ? `${region1.name} is significantly warmer.` : tempDiff < -2 ? `${region2.name} is significantly warmer.` : 'Both regions have similar temperatures.'}
                ${Math.abs(humidityDiff) > 15 ? ` Humidity varies significantly between regions.` : ''}
                </p>
            </div>
        `;
    } catch (error) {
        container.innerHTML = '<div class="notice notice-warning"><p>Unable to compare weather data. Please try again.</p></div>';
    }
}

// ===== PLANTING CALENDAR =====
function updatePlantingCalendar(weatherData) {
    const container = document.getElementById('plantingCalendar');
    const activityContainer = document.getElementById('activityGrid');
    
    if (!weatherData || !weatherData.daily) return;
    
    const daily = weatherData.daily;
    let html = '';
    
    for (let i = 0; i < Math.min(7, daily.time.length); i++) {
        const date = new Date(daily.time[i]);
        const rainProb = daily.precipitation_probability_max[i];
        const maxTemp = daily.temperature_2m_max[i];
        const minTemp = daily.temperature_2m_min[i];
        const weatherCode = daily.weather_code[i];
        
        // Calculate planting score
        let score = 100;
        if (rainProb > 70) score -= 40;
        else if (rainProb > 40) score -= 20;
        
        if (weatherCode >= 95) score -= 50; // Thunderstorm
        else if (weatherCode >= 61) score -= 30; // Rain
        
        if (maxTemp > 38 || minTemp < 10) score -= 30;
        
        let quality = 'excellent';
        if (score < 40) quality = 'poor';
        else if (score < 60) quality = 'fair';
        else if (score < 80) quality = 'good';
        
        const dayName = i === 0 ? 'Today' : date.toLocaleDateString('en-US', { weekday: 'short' });
        
        html += `
            <div class="planting-day planting-${quality}">
                <div class="day-name">${dayName}</div>
                <div class="day-date">${date.getDate()}</div>
                <div class="day-score">${score}%</div>
                <div class="day-indicator dot-${quality}"></div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    
    // Activity recommendations
    const activities = [
        { name: 'Planting', icon: 'agriculture', best: weatherData.daily.precipitation_probability_max[0] < 40 && weatherData.current.temperature_2m > 15 },
        { name: 'Watering', icon: 'water_drop', best: weatherData.daily.precipitation_probability_max[0] < 30 },
        { name: 'Fertilizing', icon: 'science', best: weatherData.current.wind_speed_10m < 15 && weatherData.daily.precipitation_probability_max[0] < 50 },
        { name: 'Harvesting', icon: 'inventory', best: weatherData.daily.precipitation_probability_max[0] < 20 },
        { name: 'Spraying', icon: 'sanitizer', best: weatherData.current.wind_speed_10m < 10 && weatherData.daily.precipitation_probability_max[0] < 30 },
        { name: 'Soil Work', icon: 'landscape', best: weatherData.daily.precipitation_probability_max[0] < 40 }
    ];
    
    activityContainer.innerHTML = activities.map(act => `
        <div class="activity-item ${act.best ? 'activity-recommended' : 'activity-not-recommended'}">
            <span class="material-icons">${act.icon}</span>
            <span>${act.name}</span>
            <span class="material-icons activity-status">${act.best ? 'check_circle' : 'cancel'}</span>
        </div>
    `).join('');
}

// ===== HISTORICAL WEATHER =====
let historicalChart = null;

async function loadHistoricalData() {
    const days = parseInt(document.getElementById('historicalPeriod').value);
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);
    
    const startStr = startDate.toISOString().split('T')[0];
    const endStr = endDate.toISOString().split('T')[0];
    
    try {
        const response = await fetch(`https://archive-api.open-meteo.com/v1/archive?latitude=${currentLat}&longitude=${currentLon}&start_date=${startStr}&end_date=${endStr}&daily=temperature_2m_max,temperature_2m_min,temperature_2m_mean,precipitation_sum,weather_code&timezone=auto`);
        const data = await response.json();
        
        if (data.daily) {
            displayHistoricalData(data);
        }
    } catch (error) {
        console.error('Historical data error:', error);
    }
}

function displayHistoricalData(data) {
    const daily = data.daily;
    
    // Calculate statistics
    const temps = daily.temperature_2m_mean.filter(t => t !== null);
    const avgTemp = temps.reduce((a, b) => a + b, 0) / temps.length;
    const totalRain = daily.precipitation_sum.reduce((a, b) => a + (b || 0), 0);
    const sunnyDays = daily.weather_code.filter(c => c <= 3).length;
    const rainyDays = daily.weather_code.filter(c => c >= 61 && c <= 67).length;
    
    document.getElementById('histAvgTemp').textContent = `${avgTemp.toFixed(1)}°C`;
    document.getElementById('histTotalRain').textContent = `${totalRain.toFixed(1)} mm`;
    document.getElementById('histSunnyDays').textContent = sunnyDays;
    document.getElementById('histRainyDays').textContent = rainyDays;
    
    // Update chart
    const ctx = document.getElementById('historicalChart').getContext('2d');
    
    if (historicalChart) {
        historicalChart.destroy();
    }
    
    historicalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: daily.time.map(t => new Date(t).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
            datasets: [
                {
                    label: 'Max Temp (°C)',
                    data: daily.temperature_2m_max,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Min Temp (°C)',
                    data: daily.temperature_2m_min,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Rainfall (mm)',
                    data: daily.precipitation_sum,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.3)',
                    type: 'bar',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Temperature (°C)' }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Rainfall (mm)' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

// ===== ALERT PREFERENCES =====
function saveAlertPreferences() {
    const preferences = {
        alerts: {
            rain: document.getElementById('alertRain').checked,
            storm: document.getElementById('alertStorm').checked,
            heat: document.getElementById('alertHeat').checked,
            frost: document.getElementById('alertFrost').checked,
            flood: document.getElementById('alertFlood').checked,
            drought: document.getElementById('alertDrought').checked
        },
        methods: {
            email: document.getElementById('notifyEmail').checked,
            sms: document.getElementById('notifySMS').checked,
            push: document.getElementById('notifyPush').checked
        }
    };
    
    // Save to localStorage and optionally to server
    localStorage.setItem('weatherAlertPreferences', JSON.stringify(preferences));
    
    // Send to server
    fetch(baseUrl + 'api/handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'save_weather_preferences',
            preferences: preferences
        })
    }).then(() => {
        showNotification('Alert preferences saved successfully!', 'success');
    }).catch(() => {
        showNotification('Preferences saved locally. Server sync failed.', 'info');
    });
}

function loadAlertPreferences() {
    const saved = localStorage.getItem('weatherAlertPreferences');
    if (saved) {
        const preferences = JSON.parse(saved);
        if (preferences.alerts) {
            document.getElementById('alertRain').checked = preferences.alerts.rain ?? true;
            document.getElementById('alertStorm').checked = preferences.alerts.storm ?? true;
            document.getElementById('alertHeat').checked = preferences.alerts.heat ?? false;
            document.getElementById('alertFrost').checked = preferences.alerts.frost ?? false;
            document.getElementById('alertFlood').checked = preferences.alerts.flood ?? true;
            document.getElementById('alertDrought').checked = preferences.alerts.drought ?? false;
        }
        if (preferences.methods) {
            document.getElementById('notifyEmail').checked = preferences.methods.email ?? true;
            document.getElementById('notifySMS').checked = preferences.methods.sms ?? false;
            document.getElementById('notifyPush').checked = preferences.methods.push ?? true;
        }
    }
}

// ===== EXPORT FUNCTIONS =====
function exportWeatherPDF() {
    // Create printable version
    const printContent = generateWeatherReport();
    const printWindow = window.open('', '_blank');
    const scriptTag = '<scr' + 'ipt>window.onload = function() { window.print(); }<\/scr' + 'ipt>';
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Weather Report - ${currentRegion}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #557A46; }
                .report-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
                .report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
                .stat-box { text-align: center; padding: 10px; background: #f5f5f5; border-radius: 8px; }
                .stat-value { font-size: 24px; font-weight: bold; color: #557A46; }
                .stat-label { font-size: 12px; color: #666; }
                @media print { body { -webkit-print-color-adjust: exact; } }
            </style>
        </head>
        <body>
            ${printContent}
            ${scriptTag}
        </body>
        </html>
    `);
}

function exportWeatherExcel() {
    if (!currentWeatherData) {
        showNotification('No weather data available to export', 'error');
        return;
    }
    
    const daily = currentWeatherData.daily;
    let csv = 'Date,Max Temp (°C),Min Temp (°C),Precipitation (mm),Rain Probability (%),Condition\n';
    
    for (let i = 0; i < daily.time.length; i++) {
        csv += `${daily.time[i]},${daily.temperature_2m_max[i]},${daily.temperature_2m_min[i]},${daily.precipitation_sum[i]},${daily.precipitation_probability_max[i]},${weatherCodes[daily.weather_code[i]]?.desc || 'Unknown'}\n`;
    }
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `weather_report_${currentRegion}_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Excel file downloaded!', 'success');
}

function printWeatherReport() {
    exportWeatherPDF();
}

function generateWeatherReport() {
    const current = currentWeatherData?.current;
    const daily = currentWeatherData?.daily;
    
    if (!current) return '<p>No data available</p>';
    
    return `
        <h1>🌾 Smart Chashi Weather Report</h1>
        <p><strong>Location:</strong> ${currentRegion}, Bangladesh</p>
        <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
        
        <div class="report-section">
            <h2>Current Weather</h2>
            <div class="report-grid">
                <div class="stat-box">
                    <div class="stat-value">${current.temperature_2m}°C</div>
                    <div class="stat-label">Temperature</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${current.relative_humidity_2m}%</div>
                    <div class="stat-label">Humidity</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${current.wind_speed_10m} km/h</div>
                    <div class="stat-label">Wind Speed</div>
                </div>
            </div>
        </div>
        
        <div class="report-section">
            <h2>7-Day Forecast</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f5f5f5;">
                    <th style="padding: 8px; border: 1px solid #ddd;">Date</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Max</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Min</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Rain</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Condition</th>
                </tr>
                ${daily.time.map((t, i) => `
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">${t}</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${daily.temperature_2m_max[i]}°C</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${daily.temperature_2m_min[i]}°C</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${daily.precipitation_sum[i]} mm</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${weatherCodes[daily.weather_code[i]]?.desc || '-'}</td>
                    </tr>
                `).join('')}
            </table>
        </div>
        
        <div class="report-section">
            <h2>Farming Recommendations</h2>
            <p>Based on current weather conditions, please follow the recommendations displayed in the Smart Chashi dashboard.</p>
        </div>
        
        <p style="text-align: center; color: #666; margin-top: 30px;">
            Generated by Smart Chashi - AI Powered Smart Farming<br>
            © ${new Date().getFullYear()} All Rights Reserved
        </p>
    `;
}

function shareWeatherReport() {
    if (navigator.share) {
        navigator.share({
            title: `Weather Report - ${currentRegion}`,
            text: `Current weather in ${currentRegion}: ${currentWeatherData?.current?.temperature_2m}°C, ${weatherCodes[currentWeatherData?.current?.weather_code]?.desc || 'Variable'}`,
            url: window.location.href
        });
    } else {
        copyWeatherLink();
    }
}

function shareToWhatsApp() {
    const text = encodeURIComponent(`🌾 Weather in ${currentRegion}: ${currentWeatherData?.current?.temperature_2m}°C - ${weatherCodes[currentWeatherData?.current?.weather_code]?.desc || 'Check it out!'}\n\n${window.location.href}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

function shareToFacebook() {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank');
}

function shareToTwitter() {
    const text = encodeURIComponent(`Weather in ${currentRegion}: ${currentWeatherData?.current?.temperature_2m}°C - Smart Chashi Farming`);
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(window.location.href)}`, '_blank');
}

function copyWeatherLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        showNotification('Link copied to clipboard!', 'success');
    });
}

function copyEmbedCode() {
    const code = document.getElementById('embedCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        showNotification('Embed code copied!', 'success');
    });
}

// Update widget preview
function updateWidgetPreview() {
    if (currentWeatherData?.current) {
        document.getElementById('widgetLocation').textContent = currentRegion;
        document.getElementById('widgetTemp').textContent = `${Math.round(currentWeatherData.current.temperature_2m)}°C`;
        document.getElementById('widgetCondition').textContent = weatherCodes[currentWeatherData.current.weather_code]?.desc || 'Variable';
    }
}

// ===== ADVANCED WEATHER METRICS =====
function updateAdvancedMetrics(data) {
    const current = data.current;
    const temp = current.temperature_2m;
    const humidity = current.relative_humidity_2m;
    const windSpeed = current.wind_speed_10m;
    
    // Wind Direction (if available, otherwise estimate)
    const windDir = current.wind_direction_10m || Math.random() * 360;
    document.querySelector('.compass-needle').style.transform = `rotate(${windDir}deg)`;
    document.getElementById('windDirection').textContent = `${Math.round(windDir)}°`;
    document.getElementById('windDirectionLabel').textContent = getWindDirectionLabel(windDir);
    
    // Heat Index Calculation
    const heatIndex = calculateHeatIndex(temp, humidity);
    document.getElementById('heatIndex').textContent = `${heatIndex.toFixed(1)}°C`;
    document.getElementById('heatIndexLabel').textContent = getHeatIndexLabel(heatIndex);
    const heatProgress = Math.min(126, (heatIndex / 50) * 126);
    document.getElementById('heatIndexArc').style.strokeDasharray = `${heatProgress} 126`;
    
    // Dew Point Calculation
    const dewPoint = calculateDewPoint(temp, humidity);
    document.getElementById('dewPoint').textContent = `${dewPoint.toFixed(1)}°C`;
    document.getElementById('dewPointLabel').textContent = getDewPointLabel(dewPoint);
    const dewProgress = Math.min(126, ((dewPoint + 10) / 40) * 126);
    document.getElementById('dewPointArc').style.strokeDasharray = `${dewProgress} 126`;
    
    // Visibility (estimated based on weather conditions)
    const visibility = estimateVisibility(current.weather_code, humidity);
    document.getElementById('visibility').textContent = `${visibility} km`;
    document.getElementById('visibilityLabel').textContent = getVisibilityLabel(visibility);
    updateVisibilityIndicator(visibility);
}

function getWindDirectionLabel(deg) {
    const directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    const index = Math.round(deg / 22.5) % 16;
    return directions[index];
}

function calculateHeatIndex(temp, humidity) {
    if (temp < 27) return temp;
    const c1 = -8.78469475556, c2 = 1.61139411, c3 = 2.33854883889;
    const c4 = -0.14611605, c5 = -0.012308094, c6 = -0.0164248277778;
    const c7 = 0.002211732, c8 = 0.00072546, c9 = -0.000003582;
    const hi = c1 + c2*temp + c3*humidity + c4*temp*humidity + c5*temp*temp + 
               c6*humidity*humidity + c7*temp*temp*humidity + c8*temp*humidity*humidity + 
               c9*temp*temp*humidity*humidity;
    return hi;
}

function getHeatIndexLabel(hi) {
    if (hi >= 54) return 'Extreme Danger';
    if (hi >= 41) return 'Danger';
    if (hi >= 32) return 'Extreme Caution';
    if (hi >= 27) return 'Caution';
    return 'Comfortable';
}

function calculateDewPoint(temp, humidity) {
    const a = 17.27, b = 237.7;
    const alpha = ((a * temp) / (b + temp)) + Math.log(humidity / 100);
    return (b * alpha) / (a - alpha);
}

function getDewPointLabel(dp) {
    if (dp >= 26) return 'Severely uncomfortable';
    if (dp >= 24) return 'Very humid';
    if (dp >= 21) return 'Somewhat uncomfortable';
    if (dp >= 18) return 'Comfortable';
    if (dp >= 13) return 'Very comfortable';
    return 'Dry';
}

function estimateVisibility(weatherCode, humidity) {
    if (weatherCode >= 45 && weatherCode <= 48) return 0.5; // Fog
    if (weatherCode >= 95) return 2; // Thunderstorm
    if (weatherCode >= 61 && weatherCode <= 67) return 5; // Rain
    if (humidity > 90) return 8;
    if (humidity > 70) return 12;
    return 20;
}

function getVisibilityLabel(vis) {
    if (vis < 1) return 'Very Poor';
    if (vis < 4) return 'Poor';
    if (vis < 10) return 'Moderate';
    return 'Good';
}

function updateVisibilityIndicator(vis) {
    const indicator = document.getElementById('visibilityIndicator');
    indicator.style.opacity = Math.min(1, vis / 20);
}

// ===== PEST & DISEASE RISK =====
function updatePestRisk(data) {
    const current = data.current;
    const temp = current.temperature_2m;
    const humidity = current.relative_humidity_2m;
    const rain = current.precipitation;
    
    // Calculate risks based on weather conditions
    let fungalRisk = 0, insectRisk = 0, bacterialRisk = 0, weedRisk = 0;
    
    // Fungal diseases thrive in humid, warm conditions
    if (humidity > 80) fungalRisk += 40;
    else if (humidity > 60) fungalRisk += 20;
    if (temp >= 20 && temp <= 30) fungalRisk += 30;
    if (rain > 5) fungalRisk += 30;
    
    // Insect activity increases with warm weather
    if (temp >= 25 && temp <= 35) insectRisk += 50;
    else if (temp >= 20) insectRisk += 30;
    if (humidity > 50 && humidity < 80) insectRisk += 30;
    if (rain < 5) insectRisk += 20;
    
    // Bacterial diseases spread in wet, warm conditions
    if (humidity > 75) bacterialRisk += 35;
    if (temp >= 25 && temp <= 35) bacterialRisk += 35;
    if (rain > 2) bacterialRisk += 30;
    
    // Weed growth
    if (temp >= 20 && temp <= 35) weedRisk += 40;
    if (rain > 0 || humidity > 60) weedRisk += 40;
    if (current.uv_index > 5) weedRisk += 20;
    
    // Cap at 100
    fungalRisk = Math.min(100, fungalRisk);
    insectRisk = Math.min(100, insectRisk);
    bacterialRisk = Math.min(100, bacterialRisk);
    weedRisk = Math.min(100, weedRisk);
    
    // Update UI
    updateRiskCategory('fungalRisk', fungalRisk);
    updateRiskCategory('insectRisk', insectRisk);
    updateRiskCategory('bacterialRisk', bacterialRisk);
    updateRiskCategory('weedRisk', weedRisk);
    
    // Overall risk
    const overallRisk = Math.round((fungalRisk + insectRisk + bacterialRisk + weedRisk) / 4);
    document.getElementById('riskPercentage').textContent = overallRisk;
    
    const riskCircle = document.getElementById('riskCircle');
    const dashOffset = 283 - (overallRisk / 100) * 283;
    riskCircle.style.strokeDashoffset = dashOffset;
    riskCircle.style.stroke = overallRisk > 70 ? '#e74c3c' : overallRisk > 40 ? '#f39c12' : '#27ae60';
    
    // Analysis text
    let analysisText = '';
    if (overallRisk > 70) {
        analysisText = '⚠️ HIGH RISK: Weather conditions are highly favorable for pests and diseases. Take preventive measures immediately. Monitor crops closely and consider protective spraying.';
    } else if (overallRisk > 40) {
        analysisText = '⚡ MODERATE RISK: Some pest and disease pressure expected. Regular monitoring recommended. Keep fungicides and pesticides ready.';
    } else {
        analysisText = '✅ LOW RISK: Current weather conditions are not favorable for major pest and disease outbreaks. Continue routine monitoring.';
    }
    document.getElementById('riskAnalysisText').textContent = analysisText;
    
    // Generate alerts
    generatePestAlerts(fungalRisk, insectRisk, bacterialRisk, weedRisk);
}

function updateRiskCategory(id, risk) {
    const element = document.getElementById(id);
    const fill = element.querySelector('.risk-fill');
    const text = element.querySelector('.risk-text');
    
    fill.style.width = `${risk}%`;
    fill.style.background = risk > 70 ? '#e74c3c' : risk > 40 ? '#f39c12' : '#27ae60';
    text.textContent = risk > 70 ? 'High' : risk > 40 ? 'Moderate' : 'Low';
}

function generatePestAlerts(fungal, insect, bacterial, weed) {
    const alertsContainer = document.getElementById('pestAlerts');
    let alerts = [];
    
    if (fungal > 60) alerts.push({ icon: 'spa', text: 'High fungal disease risk. Apply fungicide if symptoms appear.', level: 'danger' });
    if (insect > 60) alerts.push({ icon: 'pest_control', text: 'Increased insect activity expected. Check for aphids and borers.', level: 'warning' });
    if (bacterial > 60) alerts.push({ icon: 'coronavirus', text: 'Bacterial disease conditions present. Avoid overhead irrigation.', level: 'danger' });
    if (weed > 70) alerts.push({ icon: 'grass', text: 'Favorable conditions for weed growth. Consider pre-emergent herbicide.', level: 'info' });
    
    if (alerts.length === 0) {
        alertsContainer.innerHTML = '<div class="pest-alert success"><span class="material-icons">verified</span> No immediate pest or disease threats detected.</div>';
    } else {
        alertsContainer.innerHTML = alerts.map(a => `
            <div class="pest-alert ${a.level}">
                <span class="material-icons">${a.icon}</span>
                ${a.text}
            </div>
        `).join('');
    }
}

// ===== IRRIGATION CALCULATOR =====
function calculateIrrigation() {
    const fieldSize = parseFloat(document.getElementById('fieldSize').value);
    const fieldUnit = document.getElementById('fieldUnit').value;
    const cropType = document.getElementById('irrigationCrop').value;
    const system = document.getElementById('irrigationSystem').value;
    
    if (!currentWeatherData) {
        showNotification('Weather data not loaded yet', 'error');
        return;
    }
    
    const current = currentWeatherData.current;
    const daily = currentWeatherData.daily;
    
    // Convert to hectares
    let hectares = fieldSize;
    if (fieldUnit === 'acre') hectares = fieldSize * 0.4047;
    if (fieldUnit === 'bigha') hectares = fieldSize * 0.1338;
    
    // Crop water requirements (mm/day)
    const cropWaterNeeds = {
        rice: 8, wheat: 4, vegetables: 5, sugarcane: 6, jute: 5, maize: 5, potato: 4
    };
    
    // System efficiency
    const systemEfficiency = {
        flood: 0.5, drip: 0.9, sprinkler: 0.75, furrow: 0.6
    };
    
    const baseWaterNeed = cropWaterNeeds[cropType] || 5;
    const efficiency = systemEfficiency[system] || 0.6;
    
    // Adjust for weather
    let adjustedNeed = baseWaterNeed;
    const temp = current.temperature_2m;
    const humidity = current.relative_humidity_2m;
    const rain = daily.precipitation_sum[0];
    const et = daily.et0_fao_evapotranspiration ? daily.et0_fao_evapotranspiration[0] : 5;
    
    // Temperature adjustment
    if (temp > 35) adjustedNeed *= 1.3;
    else if (temp > 30) adjustedNeed *= 1.15;
    else if (temp < 20) adjustedNeed *= 0.8;
    
    // Humidity adjustment
    if (humidity < 40) adjustedNeed *= 1.2;
    else if (humidity > 80) adjustedNeed *= 0.7;
    
    // Subtract rainfall
    adjustedNeed = Math.max(0, adjustedNeed - (rain * 0.8));
    
    // Calculate totals
    const waterNeededMM = adjustedNeed / efficiency;
    const waterNeededLiters = waterNeededMM * hectares * 10000; // 1mm on 1 hectare = 10,000 liters
    
    // Duration (assuming 10 liters/second flow rate for flood)
    const flowRates = { flood: 10, drip: 2, sprinkler: 5, furrow: 8 };
    const flowRate = flowRates[system];
    const durationMinutes = Math.round(waterNeededLiters / (flowRate * 60));
    
    // Water savings compared to flood irrigation
    const floodWater = baseWaterNeed / 0.5 * hectares * 10000;
    const savings = Math.round(((floodWater - waterNeededLiters) / floodWater) * 100);
    
    // Next irrigation
    const rainProb = daily.precipitation_probability_max;
    let nextIrrigationDay = 'Tomorrow';
    for (let i = 1; i < 7; i++) {
        if (rainProb[i] > 60) {
            nextIrrigationDay = `In ${i + 1} days (rain expected)`;
            break;
        }
    }
    
    // Update UI
    document.getElementById('waterNeeded').textContent = formatNumber(Math.round(waterNeededLiters));
    document.getElementById('irrigationDuration').textContent = formatDuration(durationMinutes);
    document.getElementById('nextIrrigation').textContent = nextIrrigationDay;
    document.getElementById('waterSavings').textContent = system !== 'flood' ? `${savings}%` : 'Baseline';
    
    // Generate schedule
    generateIrrigationSchedule(adjustedNeed, efficiency, rainProb, daily.precipitation_sum);
}

function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

function formatDuration(minutes) {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}

function generateIrrigationSchedule(baseNeed, efficiency, rainProb, rainSum) {
    const container = document.getElementById('scheduleGrid');
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const today = new Date().getDay();
    
    let html = '';
    for (let i = 0; i < 7; i++) {
        const dayIndex = (today + i) % 7;
        const dayName = i === 0 ? 'Today' : days[dayIndex];
        const rainExpected = rainProb[i] > 50;
        const rainAmount = rainSum[i] || 0;
        
        let status = 'irrigate';
        let statusText = 'Irrigate';
        let statusIcon = 'water_drop';
        
        if (rainExpected && rainAmount > 5) {
            status = 'skip';
            statusText = 'Skip (Rain)';
            statusIcon = 'cloud';
        } else if (rainExpected) {
            status = 'reduce';
            statusText = 'Reduce';
            statusIcon = 'water';
        }
        
        html += `
            <div class="schedule-day schedule-${status}">
                <div class="schedule-day-name">${dayName}</div>
                <span class="material-icons">${statusIcon}</span>
                <div class="schedule-status">${statusText}</div>
            </div>
        `;
    }
    container.innerHTML = html;
}

// ===== MONSOON & SEASON TRACKER =====
function updateSeasonTracker() {
    const now = new Date();
    const month = now.getMonth() + 1;
    const day = now.getDate();
    
    // Bangladesh seasons
    const seasons = {
        'Pre-Monsoon': { start: [3, 1], end: [5, 31], crops: ['Aus Rice', 'Jute', 'Vegetables'] },
        'Monsoon': { start: [6, 1], end: [9, 30], crops: ['Aman Rice', 'Jute', 'Vegetables'] },
        'Post-Monsoon': { start: [10, 1], end: [11, 30], crops: ['Boro Rice', 'Wheat', 'Mustard'] },
        'Winter': { start: [12, 1], end: [2, 28], crops: ['Wheat', 'Potato', 'Vegetables', 'Mustard'] }
    };
    
    let currentSeason = 'Winter';
    let daysInSeason = 0;
    let seasonProgress = 0;
    let daysToNext = 0;
    
    for (const [name, data] of Object.entries(seasons)) {
        const [startMonth, startDay] = data.start;
        const [endMonth, endDay] = data.end;
        
        if ((month > startMonth || (month === startMonth && day >= startDay)) &&
            (month < endMonth || (month === endMonth && day <= endDay))) {
            currentSeason = name;
            
            // Calculate days in season
            const seasonStart = new Date(now.getFullYear(), startMonth - 1, startDay);
            const seasonEnd = new Date(now.getFullYear(), endMonth - 1, endDay);
            const totalDays = Math.ceil((seasonEnd - seasonStart) / (1000 * 60 * 60 * 24));
            daysInSeason = Math.ceil((now - seasonStart) / (1000 * 60 * 60 * 24));
            seasonProgress = Math.round((daysInSeason / totalDays) * 100);
            daysToNext = totalDays - daysInSeason;
            break;
        }
    }
    
    // Update season marker position
    const markerPositions = {
        'Pre-Monsoon': '8%',
        'Monsoon': '40%',
        'Post-Monsoon': '75%',
        'Winter': '92%'
    };
    document.getElementById('seasonMarker').style.left = markerPositions[currentSeason];
    
    // Update stats
    document.getElementById('currentSeasonName').textContent = currentSeason;
    document.getElementById('daysInSeason').textContent = daysInSeason;
    document.getElementById('seasonProgress').textContent = `${seasonProgress}%`;
    document.getElementById('nextSeasonDays').textContent = `${daysToNext} days`;
    
    // Season description
    const descriptions = {
        'Pre-Monsoon': 'Hot and humid period before the main monsoon. Ideal for early rice planting and jute cultivation. Prepare fields for monsoon crops.',
        'Monsoon': 'Heavy rainfall season. Main rice growing period (Aman). Manage waterlogging and pest pressure. Critical irrigation management period.',
        'Post-Monsoon': 'Rainfall decreases. Time for Rabi crop sowing. Prepare for winter vegetables and wheat. Good time for land preparation.',
        'Winter': 'Dry and cool. Ideal for Boro rice, wheat, and vegetable cultivation. Lower pest pressure. Irrigation required.'
    };
    document.getElementById('seasonDescription').textContent = descriptions[currentSeason];
    
    // Season crops
    const cropTags = seasons[currentSeason].crops.map(c => `<span class="crop-tag">${c}</span>`).join('');
    document.getElementById('seasonCropTags').innerHTML = cropTags;
    
    // Estimated seasonal rainfall
    const seasonalRain = { 'Pre-Monsoon': 350, 'Monsoon': 1500, 'Post-Monsoon': 200, 'Winter': 50 };
    document.getElementById('seasonRainfall').textContent = `${seasonalRain[currentSeason]} mm`;
}

// ===== VOICE WEATHER REPORT =====
let speechSynth = window.speechSynthesis;
let currentUtterance = null;

function playWeatherReport() {
    if (!currentWeatherData) {
        showNotification('Weather data not loaded', 'error');
        return;
    }
    
    const lang = document.getElementById('voiceLanguage').value;
    const speed = parseFloat(document.getElementById('voiceSpeed').value);
    
    const current = currentWeatherData.current;
    const daily = currentWeatherData.daily;
    const condition = weatherCodes[current.weather_code]?.desc || 'variable conditions';
    
    let reportText = '';
    
    if (lang === 'bn-BD') {
        reportText = `${currentRegion} এ বর্তমান আবহাওয়া। তাপমাত্রা ${Math.round(current.temperature_2m)} ডিগ্রি সেলসিয়াস। আর্দ্রতা ${current.relative_humidity_2m} শতাংশ। বাতাসের গতি ${Math.round(current.wind_speed_10m)} কিলোমিটার প্রতি ঘন্টা। আজকের সর্বোচ্চ তাপমাত্রা ${Math.round(daily.temperature_2m_max[0])} এবং সর্বনিম্ন ${Math.round(daily.temperature_2m_min[0])} ডিগ্রি।`;
    } else {
        reportText = `Current weather in ${currentRegion}. Temperature is ${Math.round(current.temperature_2m)} degrees Celsius with ${condition}. Humidity is at ${current.relative_humidity_2m} percent. Wind speed is ${Math.round(current.wind_speed_10m)} kilometers per hour. Today's high will be ${Math.round(daily.temperature_2m_max[0])} and low ${Math.round(daily.temperature_2m_min[0])} degrees. `;
        
        // Add recommendations
        if (daily.precipitation_probability_max[0] > 50) {
            reportText += `Rain is expected today with ${daily.precipitation_probability_max[0]} percent probability. Consider postponing outdoor activities. `;
        } else {
            reportText += `Weather looks favorable for farming activities today. `;
        }
    }
    
    document.getElementById('voiceTranscript').innerHTML = `<p>"${reportText}"</p>`;
    
    currentUtterance = new SpeechSynthesisUtterance(reportText);
    currentUtterance.lang = lang;
    currentUtterance.rate = speed;
    
    currentUtterance.onstart = () => {
        document.getElementById('playVoiceBtn').disabled = true;
        document.getElementById('pauseVoiceBtn').disabled = false;
        document.getElementById('stopVoiceBtn').disabled = false;
    };
    
    currentUtterance.onend = () => {
        document.getElementById('playVoiceBtn').disabled = false;
        document.getElementById('pauseVoiceBtn').disabled = true;
        document.getElementById('stopVoiceBtn').disabled = true;
    };
    
    speechSynth.speak(currentUtterance);
}

function pauseWeatherReport() {
    if (speechSynth.paused) {
        speechSynth.resume();
        document.getElementById('pauseVoiceBtn').innerHTML = '<span class="material-icons">pause</span>';
    } else {
        speechSynth.pause();
        document.getElementById('pauseVoiceBtn').innerHTML = '<span class="material-icons">play_arrow</span>';
    }
}

function stopWeatherReport() {
    speechSynth.cancel();
    document.getElementById('playVoiceBtn').disabled = false;
    document.getElementById('pauseVoiceBtn').disabled = true;
    document.getElementById('stopVoiceBtn').disabled = true;
}

function updateVoiceSettings() {
    document.getElementById('speedValue').textContent = document.getElementById('voiceSpeed').value + 'x';
}

// ===== ACCURACY FEEDBACK =====
function submitAccuracy(rating) {
    const ratings = JSON.parse(localStorage.getItem('weatherRatings') || '[]');
    ratings.push({ rating, date: new Date().toISOString(), region: currentRegion });
    localStorage.setItem('weatherRatings', JSON.stringify(ratings));
    
    // Highlight selected button
    document.querySelectorAll('.accuracy-btn').forEach(btn => btn.classList.remove('selected'));
    event.target.closest('.accuracy-btn').classList.add('selected');
    
    showNotification('Thank you for your feedback!', 'success');
    
    // Update stats
    const accurateCount = ratings.filter(r => r.rating === 'very-accurate' || r.rating === 'accurate').length;
    const totalCount = ratings.length;
    document.getElementById('totalFeedback').textContent = totalCount;
    document.getElementById('accuracyRate').textContent = Math.round((accurateCount / totalCount) * 100) + '%';
}

// Initialize new features on load
document.addEventListener('DOMContentLoaded', function() {
    loadAlertPreferences();
    updateComparison();
    updateSeasonTracker();
    setTimeout(() => {
        loadHistoricalData();
    }, 2000);
});
</script>

<style>
/* Quick Stats Row */
.weather-quick-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-card .material-icons {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stat-card .stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text);
}

.stat-card .stat-label {
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
}

/* Weather Grid */
.weather-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.weather-card-wrapper .card {
    height: 100%;
}

/* Disaster Alerts */
.disaster-alerts-container {
    padding: 1rem;
}

.disaster-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.disaster-event-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid #e74c3c;
    transition: transform 0.2s;
}

.disaster-event-card:hover {
    transform: translateX(5px);
}

.disaster-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.disaster-icon .material-icons {
    font-size: 1.5rem;
}

.disaster-info {
    flex: 1;
}

.disaster-info h5 {
    margin: 0 0 0.25rem;
    font-size: 0.9rem;
}

.disaster-category {
    margin: 0;
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
}

.disaster-distance, .disaster-date {
    margin: 0.25rem 0 0;
    font-size: 0.75rem;
    color: #888;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.disaster-distance .material-icons, .disaster-date .material-icons {
    font-size: 0.85rem;
}

.disaster-link {
    color: #666;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background 0.2s;
}

.disaster-link:hover {
    background: #eee;
    color: var(--primary);
}

/* Satellite Section */
.satellite-section {
    padding: 1rem;
}

.satellite-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.satellite-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.85rem;
    color: var(--primary)
}

.satellite-tab:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.satellite-tab.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.satellite-tab .material-icons {
    font-size: 1.1rem;
}

.satellite-image-container {
    min-height: 400px;
    background: #f5f5f5;
    border-radius: 8px;
    overflow: hidden;
}

.satellite-map-wrapper {
    width: 100%;
}

.satellite-loading, .satellite-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: #666;
}

.satellite-info {
    margin-top: 1rem;
    text-align: center;
    color: #888;
}

.satellite-info .material-icons {
    font-size: 0.9rem;
    vertical-align: middle;
}

.vegetation-legend {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f9f9f9;
    border-radius: 4px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
}

.legend-item span:first-child {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

/* AI Summary */
.ai-summary-container {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.ai-summary {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 12px;
    padding: 1.25rem;
    border-left: 4px solid #0ea5e9;
}

.ai-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.ai-header .material-icons {
    color: #0ea5e9;
}

.ai-summary p {
    margin: 0;
    line-height: 1.6;
}

.badge-ai {
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: white;
    font-size: 0.65rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
}

.ai-summary-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    color: #666;
}

/* Crop Recommendations */
.crop-recommendations-container {
    padding: 1rem;
}

.crop-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.crop-card {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    transition: all 0.2s;
}

.crop-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.crop-card.crop-optimal {
    border-color: #27ae60;
    background: linear-gradient(to bottom right, #f0fff4, white);
}

.crop-card.crop-caution {
    border-color: #f39c12;
    background: linear-gradient(to bottom right, #fffbeb, white);
}

.crop-card.crop-warning {
    border-color: #e74c3c;
    background: linear-gradient(to bottom right, #fef2f2, white);
}

.crop-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.crop-header .material-icons {
    color: var(--primary);
}

.crop-header h5 {
    margin: 0;
    font-size: 1rem;
}

.crop-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.status-indicator.status-optimal { background: #27ae60; }
.status-indicator.status-caution { background: #f39c12; }
.status-indicator.status-warning { background: #e74c3c; }

.crop-advice {
    font-size: 0.85rem;
    color: #666;
    margin: 0 0 0.5rem;
}

.crop-ranges {
    font-size: 0.75rem;
    color: #999;
    border-top: 1px solid #eee;
    padding-top: 0.5rem;
}

/* Agricultural Index */
.agri-index-container {
    padding: 1rem;
}

.agri-index-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.agri-index-card {
    background: linear-gradient(145deg, #f8f9fa, white);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    border: 1px solid #eee;
}

.agri-index-card .index-icon {
    margin-bottom: 0.5rem;
}

.agri-index-card .index-icon .material-icons {
    font-size: 2rem;
    color: var(--primary);
}

.agri-index-card .index-value {
    font-size: 1.75rem;
    font-weight: bold;
    color: var(--text);
}

.agri-index-card .index-label {
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.agri-index-card .index-status {
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    display: inline-block;
}

.index-status.status-good { background: #d4edda; color: #155724; }
.index-status.status-moderate { background: #fff3cd; color: #856404; }
.index-status.status-low { background: #d1ecf1; color: #0c5460; }
.index-status.status-high { background: #f8d7da; color: #721c24; }

/* Data Sources */
.data-sources-card {
    background: #f8f9fa;
}

.data-sources-grid {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1rem;
    flex-wrap: wrap;
}

.data-source {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.85rem;
}

.data-source img {
    height: 24px;
    width: auto;
}

.data-source .material-icons {
    font-size: 1.25rem;
    color: var(--primary);
}

/* Current Weather Display */
.current-weather-display {
    text-align: center;
}

.weather-main {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.weather-icon-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #74b9ff, #0984e3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.weather-icon-large .material-icons {
    font-size: 50px;
    color: white;
}

.weather-temp-main .weather-temp {
    font-size: 3rem;
    font-weight: bold;
    margin: 0;
    color: var(--text);
}

.weather-feels {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

.weather-condition {
    font-size: 1.2rem;
    text-transform: capitalize;
    color: #333;
    margin-bottom: 0.5rem;
}

.weather-location {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    color: #666;
    margin-bottom: 1.5rem;
}

.weather-location .material-icons {
    font-size: 1rem;
}

/* Weather Details Grid */
.weather-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 12px;
}

.weather-detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.weather-detail-item .material-icons {
    font-size: 1.5rem;
    color: var(--primary);
}

.weather-detail-item strong {
    display: block;
    font-size: 1rem;
}

.weather-detail-item small {
    color: #666;
    font-size: 0.8rem;
}

/* Location Info */
.location-info {
    padding: 0.75rem;
    background: #f0f9f4;
    border-radius: 8px;
    font-size: 0.85rem;
}

.location-info p {
    margin: 0.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.location-info .material-icons {
    font-size: 1rem;
    color: var(--primary);
}

.weather-updated {
    text-align: center;
    color: #999;
}

.weather-updated .material-icons {
    font-size: 0.9rem;
    vertical-align: middle;
}

/* Forecast Grid */
.forecast-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.forecast-card {
    background: linear-gradient(145deg, #f8f9fa, #ffffff);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #eee;
}

.forecast-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.forecast-card:first-child {
    background: linear-gradient(145deg, #e8f5e9, #f1f8e9);
    border-color: var(--primary);
}

.forecast-day {
    font-weight: bold;
    font-size: 0.9rem;
    color: var(--primary);
}

.forecast-date {
    font-size: 0.75rem;
    color: #999;
    margin-bottom: 0.5rem;
}

.forecast-icon {
    margin: 0.5rem 0;
}

.forecast-icon .material-icons {
    font-size: 2rem;
    color: #74b9ff;
}

.forecast-condition {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.forecast-temps {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.temp-high {
    color: #ff6b6b;
    font-weight: bold;
}

.temp-low {
    color: #74b9ff;
}

.forecast-details {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #888;
}

.forecast-details .material-icons {
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Hourly Forecast */
.hourly-forecast-scroll {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 1rem;
    scroll-snap-type: x mandatory;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) #f0f0f0;
}

.hourly-forecast-scroll::-webkit-scrollbar {
    height: 8px;
}

.hourly-forecast-scroll::-webkit-scrollbar-track {
    background: #f0f0f0;
    border-radius: 4px;
}

.hourly-forecast-scroll::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
}

.hourly-item {
    flex: 0 0 auto;
    background: white;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    min-width: 80px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    scroll-snap-align: start;
    transition: transform 0.2s;
}

.hourly-item:hover {
    transform: translateY(-2px);
}

.hourly-item.hourly-now {
    background: linear-gradient(135deg, var(--primary), #2d8a56);
    color: white;
}

.hourly-item.hourly-now .hourly-time {
    color: white;
    font-weight: bold;
}

.hourly-item.hourly-now .hourly-icon .material-icons {
    color: white;
}

.hourly-item.hourly-now .hourly-rain {
    color: rgba(255,255,255,0.9);
}

.hourly-time {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.hourly-icon .material-icons {
    font-size: 1.5rem;
    color: #74b9ff;
}

.hourly-temp {
    font-weight: bold;
    font-size: 1.1rem;
    margin: 0.25rem 0;
}

.hourly-rain {
    font-size: 0.75rem;
    color: #888;
}

.hourly-rain .material-icons {
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Weather Chart */
.weather-chart {
    height: 300px;
    padding: 1rem;
}

/* Recommendations */
.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.recommendation-card {
    border-left: 4px solid #ccc;
    background: #fafafa;
}

.recommendation-card.recommended {
    border-left-color: var(--success);
    background: linear-gradient(to right, rgba(40, 167, 69, 0.05), transparent);
}

.recommendation-card.recommended h4 {
    color: var(--success);
}

.recommendation-card.not-recommended {
    border-left-color: var(--danger);
    background: linear-gradient(to right, rgba(220, 53, 69, 0.05), transparent);
}

.recommendation-card.not-recommended h4 {
    color: var(--danger);
}

.recommendation-card.precaution {
    border-left-color: var(--warning);
    background: linear-gradient(to right, rgba(255, 193, 7, 0.08), transparent);
}

.recommendation-card.precaution h4 {
    color: #e67e22;
}

.recommendation-card h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.recommendation-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recommendation-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.recommendation-list li:last-child {
    border-bottom: none;
}

.recommendation-list .material-icons {
    font-size: 1rem;
}

.recommended .recommendation-list .material-icons {
    color: var(--success);
}

.not-recommended .recommendation-list .material-icons {
    color: var(--danger);
}

.precaution .recommendation-list .material-icons {
    color: #e67e22;
}

/* Alerts */
.alerts-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
}

.alerts-container .notice {
    margin: 0;
}

/* Loading States */
.weather-loading, .forecast-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #666;
}

.weather-loading .material-icons, .forecast-loading .material-icons {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 2000;
    animation: slideIn 0.3s ease;
}

.notification-success {
    background: #d4edda;
    color: #155724;
}

.notification-error {
    background: #f8d7da;
    color: #721c24;
}

.notification.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1200px) {
    .weather-quick-stats {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .agri-index-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .weather-grid {
        grid-template-columns: 1fr;
    }
    
    .weather-details-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .weather-quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .disaster-events-grid {
        grid-template-columns: 1fr;
    }
    
    .satellite-tabs {
        justify-content: center;
    }
}

@media (max-width: 600px) {
    .weather-quick-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.25rem;
    }
    
    .weather-main {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .weather-icon-large {
        width: 80px;
        height: 80px;
    }
    
    .weather-icon-large .material-icons {
        font-size: 40px;
    }
    
    .weather-temp-main .weather-temp {
        font-size: 2.5rem;
    }
    
    .forecast-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .recommendations-grid {
        grid-template-columns: 1fr;
    }
    
    .agri-index-grid {
        grid-template-columns: 1fr;
    }
    
    .crop-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .satellite-tab {
        padding: 0.4rem 0.75rem;
        font-size: 0.75rem;
    }
    
    .data-sources-grid {
        gap: 1rem;
    }
}

/* ===== SUN & MOON STYLES ===== */
.sun-moon-container {
    padding: 1.5rem;
}

.sun-moon-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.sun-card, .moon-card {
    background: linear-gradient(145deg, #f8f9fa, white);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
}

.sun-visual {
    position: relative;
    height: 100px;
    margin-bottom: 1rem;
}

.sun-arc {
    position: absolute;
    width: 80%;
    height: 70px;
    left: 10%;
    top: 10px;
    border: 3px dashed #f1c40f;
    border-bottom: none;
    border-radius: 100px 100px 0 0;
}

.sun-position {
    position: absolute;
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #f1c40f, #e67e22);
    border-radius: 50%;
    box-shadow: 0 0 20px rgba(241, 196, 15, 0.6);
    transform: translate(-50%, -50%);
    transition: all 0.5s ease;
}

.horizon-line {
    position: absolute;
    bottom: 10px;
    left: 5%;
    right: 5%;
    height: 2px;
    background: linear-gradient(to right, transparent, #333, transparent);
}

.sun-times {
    display: flex;
    justify-content: space-around;
    margin-bottom: 1rem;
}

.sun-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sun-time .material-icons {
    color: #f1c40f;
    font-size: 1.5rem;
}

.sun-time strong {
    font-size: 1.1rem;
    display: block;
}

.sun-time small {
    color: #666;
    font-size: 0.75rem;
}

.daylight-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #fff9e6;
    border-radius: 8px;
    color: #856404;
}

.moon-visual {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.moon-phase {
    font-size: 4rem;
}

.moon-info h4 {
    margin: 0 0 0.5rem;
    color: #34495e;
}

.moon-info p {
    margin: 0 0 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.moon-times {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    font-size: 0.85rem;
    color: #666;
}

.moon-times .material-icons {
    font-size: 1rem;
    vertical-align: middle;
}

.golden-hour-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.golden-hour {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #fffbeb;
    border-radius: 8px;
}

.golden-hour strong {
    display: block;
    font-size: 0.85rem;
}

.golden-hour span:last-child {
    color: #666;
    font-size: 0.85rem;
}

/* ===== COMPARISON TOOL ===== */
.comparison-container {
    padding: 1.5rem;
}

.comparison-selectors {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.comparison-select {
    flex: 1;
    max-width: 200px;
}

.comparison-select label {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.comparison-select select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
}

.comparison-vs {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
}

.comparison-table {
    background: #f9f9f9;
    border-radius: 12px;
    overflow: hidden;
}

.comparison-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.comparison-row.header {
    background: var(--primary);
    color: white;
    font-weight: bold;
}

.comparison-row:last-child {
    border-bottom: none;
}

.comparison-metric {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.comparison-metric .material-icons {
    font-size: 1.25rem;
    color: var(--primary);
}

.comparison-row.header .comparison-metric .material-icons {
    color: white;
}

.comparison-value {
    text-align: center;
    font-weight: 600;
}

.comparison-diff {
    text-align: center;
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.diff-higher {
    background: #fee2e2;
    color: #dc2626;
}

.diff-lower {
    background: #dcfce7;
    color: #16a34a;
}

.comparison-summary {
    margin-top: 1rem;
    padding: 1rem;
    background: #e8f4fd;
    border-radius: 8px;
}

.comparison-summary .material-icons {
    color: #3498db;
    vertical-align: middle;
}

/* ===== PLANTING CALENDAR ===== */
.planting-calendar-container {
    padding: 1.5rem;
}

.calendar-legend {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.dot-excellent { background: #27ae60; }
.dot-good { background: #3498db; }
.dot-fair { background: #f39c12; }
.dot-poor { background: #e74c3c; }

.planting-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.planting-day {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 2px solid #eee;
    transition: transform 0.2s;
}

.planting-day:hover {
    transform: translateY(-3px);
}

.planting-day.planting-excellent {
    border-color: #27ae60;
    background: linear-gradient(to bottom, #d4edda, white);
}

.planting-day.planting-good {
    border-color: #3498db;
    background: linear-gradient(to bottom, #d1ecf1, white);
}

.planting-day.planting-fair {
    border-color: #f39c12;
    background: linear-gradient(to bottom, #fff3cd, white);
}

.planting-day.planting-poor {
    border-color: #e74c3c;
    background: linear-gradient(to bottom, #f8d7da, white);
}

.planting-day .day-name {
    font-weight: bold;
    font-size: 0.9rem;
    color: #333;
}

.planting-day .day-date {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0.25rem 0;
}

.planting-day .day-score {
    font-size: 0.85rem;
    color: #666;
}

.planting-day .day-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin: 0.5rem auto 0;
}

.planting-activities h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
}

.activity-item.activity-recommended {
    background: #d4edda;
    color: #155724;
}

.activity-item.activity-not-recommended {
    background: #f8d7da;
    color: #721c24;
}

.activity-item .activity-status {
    margin-left: auto;
    font-size: 1.25rem;
}

/* ===== HISTORICAL WEATHER ===== */
.historical-container {
    padding: 1.5rem;
}

.historical-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.hist-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: linear-gradient(145deg, #f8f9fa, white);
    border-radius: 12px;
    border: 1px solid #eee;
}

.hist-stat .material-icons {
    font-size: 2rem;
    color: var(--primary);
}

.hist-stat-info {
    display: flex;
    flex-direction: column;
}

.hist-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text);
}

.hist-label {
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
}

.historical-chart {
    height: 300px;
}

.header-actions select {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
}

/* ===== ALERT SUBSCRIPTION ===== */
.alert-subscription-container {
    padding: 1.5rem;
}

.subscription-desc {
    color: #666;
    margin-bottom: 1.5rem;
}

.alert-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.alert-type-card {
    cursor: pointer;
}

.alert-type-card input {
    display: none;
}

.alert-type-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    transition: all 0.2s;
}

.alert-type-card input:checked + .alert-type-content {
    border-color: var(--primary);
    background: #f0f9f4;
}

.alert-type-content .material-icons {
    font-size: 2rem;
    color: #666;
}

.alert-type-card input:checked + .alert-type-content .material-icons {
    color: var(--primary);
}

.notification-methods {
    background: #f9f9f9;
    border-radius: 12px;
    padding: 1rem;
}

.notification-methods h4 {
    margin: 0 0 1rem;
    font-size: 1rem;
}

.method-options {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.method-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.method-option input {
    width: 18px;
    height: 18px;
}

/* ===== EXPORT SECTION ===== */
.export-container {
    padding: 1.5rem;
}

.export-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.export-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem 1rem;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.export-btn:hover {
    border-color: var(--primary);
    background: #f0f9f4;
}

.export-btn .material-icons {
    font-size: 2rem;
    color: var(--primary);
}

.share-links {
    text-align: center;
}

.social-share {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.social-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: transform 0.2s;
}

.social-btn:hover {
    transform: scale(1.1);
}

.social-btn img {
    width: 24px;
    height: 24px;
    filter: brightness(0) invert(1);
}

.social-btn.whatsapp { background: #25d366; }
.social-btn.facebook { background: #1877f2; }
.social-btn.twitter { background: #000; }
.social-btn.copy { background: #666; color: white; }

/* ===== WIDGET SECTION ===== */
.widget-container {
    padding: 1.5rem;
}

.widget-preview {
    display: flex;
    justify-content: center;
    margin: 1.5rem 0;
}

.mini-weather-widget {
    background: linear-gradient(135deg, var(--primary), #2d8a56);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    text-align: center;
    min-width: 200px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.widget-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.widget-temp {
    font-size: 2.5rem;
    font-weight: bold;
}

.widget-condition {
    font-size: 0.9rem;
    opacity: 0.9;
}

.widget-code {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 1rem;
}

.widget-code label {
    font-size: 0.85rem;
    color: #666;
    display: block;
    margin-bottom: 0.5rem;
}

.code-block {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #1e1e1e;
    padding: 0.75rem;
    border-radius: 6px;
}

.code-block code {
    flex: 1;
    color: #9cdcfe;
    font-size: 0.8rem;
    overflow-x: auto;
    white-space: nowrap;
}

.code-block .btn {
    flex-shrink: 0;
    background: #333;
    color: white;
    border: none;
}

/* ===== ADVANCED WEATHER METRICS ===== */
.advanced-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    padding: 1.5rem;
}

.metric-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* Wind Compass */
.wind-compass {
    width: 100px;
    height: 100px;
    margin: 0 auto;
    position: relative;
    border: 3px solid #e2e8f0;
    border-radius: 50%;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    box-shadow: 
        inset 0 2px 10px rgba(0,0,0,0.05),
        0 4px 15px rgba(0,0,0,0.08);
}

.compass-rose {
    position: absolute;
    width: 100%;
    height: 100%;
    font-size: 0.7rem;
    font-weight: 700;
}

.compass-dir {
    position: absolute;
    color: #64748b;
    text-transform: uppercase;
}

.compass-dir.n { 
    top: 6px; 
    left: 50%; 
    transform: translateX(-50%); 
    color: #e74c3c;
    font-size: 0.8rem;
}
.compass-dir.e { 
    right: 8px; 
    top: 50%; 
    transform: translateY(-50%); 
}
.compass-dir.s { 
    bottom: 6px; 
    left: 50%; 
    transform: translateX(-50%); 
}
.compass-dir.w { 
    left: 8px; 
    top: 50%; 
    transform: translateY(-50%); 
}

.compass-needle {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 6px;
    height: 40px;
    background: linear-gradient(to bottom, #e74c3c 0%, #e74c3c 50%, #3b82f6 50%, #3b82f6 100%);
    transform-origin: center bottom;
    transform: translateX(-50%) translateY(-100%) rotate(0deg);
    border-radius: 3px 3px 1px 1px;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.compass-needle::before {
    content: '';
    position: absolute;
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 8px solid #e74c3c;
}

.compass-center {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 14px;
    height: 14px;
    background: linear-gradient(135deg, #475569 0%, #1e293b 100%);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    z-index: 10;
}

.compass-center::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 4px;
    height: 4px;
    background: rgba(255,255,255,0.4);
    border-radius: 50%;
}

/* Legacy compass classes */
.compass-container {
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
    position: relative;
}

.compass-ring {
    width: 100%;
    height: 100%;
    border: 4px solid #ddd;
    border-radius: 50%;
    position: relative;
}

.compass-directions {
    position: absolute;
    width: 100%;
    height: 100%;
    font-size: 0.7rem;
    font-weight: bold;
    color: #666;
}

.compass-directions span {
    position: absolute;
}

.compass-directions .n { top: 2px; left: 50%; transform: translateX(-50%); }
.compass-directions .e { right: 2px; top: 50%; transform: translateY(-50%); }
.compass-directions .s { bottom: 2px; left: 50%; transform: translateX(-50%); }
.compass-directions .w { left: 2px; top: 50%; transform: translateY(-50%); }

.metric-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.gauge-svg {
    width: 120px;
    height: 70px;
}

.gauge-bg {
    fill: none;
    stroke: #e0e0e0;
    stroke-width: 10;
}

.gauge-fill {
    fill: none;
    stroke: var(--primary-color);
    stroke-width: 10;
    stroke-linecap: round;
    transition: stroke-dasharray 0.5s ease;
}

.gauge-fill.heat {
    stroke: #e74c3c;
}

.gauge-fill.dew {
    stroke: #3498db;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.metric-label {
    font-size: 0.85rem;
    color: #666;
}

.visibility-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin: 1rem 0 0.5rem;
}

.visibility-indicator {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c, #f39c12, #27ae60);
    border-radius: 4px;
    transition: opacity 0.5s ease;
}

/* ===== PEST & DISEASE RISK ===== */
.pest-risk-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    padding: 1.5rem;
}

.risk-summary {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.risk-level-indicator {
    text-align: center;
}

.risk-circle {
    position: relative;
    width: 180px;
    height: 180px;
}

.risk-circle svg {
    width: 100%;
    height: 100%;
}

.risk-circle circle {
    transition: all 0.5s ease;
}

.risk-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.risk-value span {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    display: block;
    line-height: 1;
}

.risk-value small {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

.risk-details {
    text-align: center;
    padding: 0 1rem;
}

.risk-details h4 {
    color: #1e293b;
    margin-bottom: 0.75rem;
    font-size: 1.1rem;
}

.risk-details p {
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.6;
}

.pest-categories {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.pest-category {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.pest-category:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.pest-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    flex-shrink: 0;
}

.pest-icon .material-icons {
    font-size: 1.5rem;
    color: #6366f1;
}

#fungalRisk .pest-icon .material-icons { color: #22c55e; }
#insectRisk .pest-icon .material-icons { color: #f59e0b; }
#bacterialRisk .pest-icon .material-icons { color: #ef4444; }
#weedRisk .pest-icon .material-icons { color: #10b981; }

.pest-info {
    flex: 1;
    min-width: 0;
}

.pest-info h5 {
    margin: 0 0 0.5rem 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
}

.risk-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.risk-fill {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #16a34a);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.risk-text {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.6rem;
    background: #dcfce7;
    color: #166534;
    border-radius: 4px;
}

/* Risk level colors */
.pest-category[data-risk="high"] .risk-fill { background: linear-gradient(90deg, #ef4444, #dc2626); }
.pest-category[data-risk="high"] .risk-text { background: #fee2e2; color: #991b1b; }
.pest-category[data-risk="moderate"] .risk-fill { background: linear-gradient(90deg, #f59e0b, #d97706); }
.pest-category[data-risk="moderate"] .risk-text { background: #fef3c7; color: #92400e; }

.pest-alerts {
    grid-column: 1 / -1;
    margin-top: 1rem;
}

.pest-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1.25rem;
    border-radius: 10px;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.pest-alert .material-icons {
    font-size: 1.25rem;
}

.pest-alert.danger {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #991b1b;
    border: 1px solid #fecaca;
}

.pest-alert.warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #92400e;
    border: 1px solid #fde68a;
}

.pest-alert.info {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.pest-alert.success {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    color: #166534;
    border: 1px solid #bbf7d0;
}

.risk-analysis {
    background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem;
}

.risk-analysis h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.pest-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.pest-alert.danger {
    background: #fee2e2;
    color: #991b1b;
}

.pest-alert.warning {
    background: #fef3c7;
    color: #92400e;
}

.pest-alert.info {
    background: #dbeafe;
    color: #1e40af;
}

.pest-alert.success {
    background: #d1fae5;
    color: #065f46;
}

/* ===== IRRIGATION CALCULATOR ===== */
.irrigation-container {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 2rem;
    padding: 1.5rem;
    align-items: start;
}

.irrigation-inputs {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.irrigation-inputs .input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.irrigation-inputs label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #2e7d32;
}

.irrigation-inputs label .material-icons {
    font-size: 1.1rem;
    color: #3498db;
}

.input-with-unit {
    display: flex;
    gap: 0.5rem;
}

.input-with-unit input {
    width: 80px;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    text-align: center;
}

.input-with-unit select,
.irrigation-inputs select {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 1rem;
}

.irrigation-results {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.irrigation-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #ebf8ff 0%, #dbeafe 100%);
    padding: 1.25rem;
    border-radius: 12px;
    min-height: 90px;
}

.irrigation-stat .material-icons {
    font-size: 2.2rem;
    color: #3498db;
    background: white;
    padding: 0.5rem;
    border-radius: 10px;
}

.irrigation-stat div {
    flex: 1;
}

.irrigation-stat strong {
    display: block;
    font-size: 1.4rem;
    color: #1e3a5f;
    margin-bottom: 0.25rem;
}

.irrigation-stat small {
    color: #5a7a9a;
    font-size: 0.85rem;
}

.irrigation-schedule {
    grid-column: 1 / -1;
    margin-top: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.irrigation-schedule h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    color: #2e7d32;
}

.schedule-section {
    padding: 1.5rem;
    border-top: 1px solid #eee;
}

.schedule-section h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.schedule-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.75rem;
}

.schedule-day {
    text-align: center;
    padding: 1rem 0.5rem;
    border-radius: 10px;
    background: #f8f9fa;
}

.schedule-day-name {
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.schedule-day .material-icons {
    font-size: 1.5rem;
    margin: 0.25rem 0;
}

.schedule-status {
    font-size: 0.75rem;
}

.schedule-irrigate {
    background: #dbeafe;
    color: #1e40af;
}

.schedule-skip {
    background: #d1fae5;
    color: #065f46;
}

.schedule-reduce {
    background: #fef3c7;
    color: #92400e;
}

/* ===== MONSOON TRACKER ===== */
.monsoon-container {
    padding: 1.5rem;
}

.season-timeline {
    position: relative;
    margin: 1.5rem 0;
    padding: 0.5rem 0;
}

.timeline-bar {
    display: flex;
    height: 60px;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.season-segment {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 500;
    padding: 0.5rem;
    transition: all 0.3s ease;
}

.season-segment:hover {
    filter: brightness(1.1);
}

.season-segment span {
    font-size: 0.85rem;
    font-weight: 600;
}

.season-segment small {
    font-size: 0.7rem;
    opacity: 0.9;
}

.season-segment.pre-monsoon {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.season-segment.monsoon {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.season-segment.post-monsoon {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.season-segment.winter {
    background: linear-gradient(135deg, #0ea5e9, #38bdf8);
}

.current-marker {
    position: absolute;
    top: -8px;
    width: 4px;
    height: calc(100% + 16px);
    background: #e74c3c;
    border-radius: 2px;
    z-index: 10;
    transition: left 0.5s ease;
    box-shadow: 0 0 10px rgba(231, 76, 60, 0.5);
}

.current-marker::before {
    content: '▼';
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    color: #e74c3c;
    font-size: 14px;
}

.monsoon-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin: 1.5rem 0;
}

.monsoon-stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1.25rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.monsoon-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.monsoon-stat-card .material-icons {
    font-size: 2rem;
    color: #3b82f6;
    background: #dbeafe;
    padding: 0.75rem;
    border-radius: 12px;
}

.monsoon-stat-card strong {
    display: block;
    font-size: 1.35rem;
    color: #1e293b;
}

.monsoon-stat-card small {
    color: #64748b;
    font-size: 0.8rem;
}

.season-info {
    margin-top: 1rem;
}

.current-season-card {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid #a7f3d0;
}

.current-season-card h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #065f46;
    margin-bottom: 1rem;
}

.current-season-card h4 .material-icons {
    color: #10b981;
}

.current-season-card p {
    color: #047857;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.season-crops {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #a7f3d0;
}

.season-crops strong {
    display: block;
    margin-bottom: 0.75rem;
    color: #065f46;
}

.crop-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.crop-tag {
    display: inline-block;
    background: white;
    padding: 0.4rem 0.85rem;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #065f46;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #d1fae5;
}

/* ===== VOICE WEATHER REPORT ===== */
.voice-report-container {
    padding: 1.5rem;
    text-align: center;
}

.voice-container {
    padding: 1.5rem;
}

.voice-controls {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 20px;
}

.voice-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #94a3b8, #64748b);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.voice-btn .material-icons {
    font-size: 1.5rem;
}

.voice-btn:hover:not(:disabled) {
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(0,0,0,0.25);
}

.voice-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #cbd5e1;
    box-shadow: none;
}

.voice-btn.play-btn {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

.voice-btn.play-btn:hover:not(:disabled) {
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.5);
}

.voice-btn.play-btn .material-icons {
    font-size: 2rem;
}

/* Pause button when active */
#pauseVoiceBtn:not(:disabled) {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

/* Stop button when active */
#stopVoiceBtn:not(:disabled) {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.voice-settings {
    display: flex;
    gap: 2.5rem;
    justify-content: center;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    padding: 1.25rem 1.5rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.voice-setting {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.voice-setting label {
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.voice-setting select {
    padding: 0.6rem 2rem 0.6rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: white;
    font-size: 0.95rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    transition: all 0.2s ease;
}

.voice-setting select:hover {
    border-color: var(--primary-color);
}

.voice-setting select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(87, 122, 70, 0.15);
}

.voice-setting input[type="range"] {
    width: 120px;
    height: 6px;
    cursor: pointer;
    -webkit-appearance: none;
    background: #e5e7eb;
    border-radius: 3px;
}

.voice-setting input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    background: var(--primary-color);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.voice-setting input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.1);
}

.voice-setting input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    background: var(--primary-color);
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

#speedValue {
    font-weight: 700;
    color: var(--primary-color);
    min-width: 35px;
    font-size: 0.95rem;
}

.voice-transcript {
    background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
    padding: 1.5rem;
    border-radius: 12px;
    min-height: 100px;
    border-left: 4px solid #f59e0b;
}

.voice-transcript p {
    font-style: italic;
    color: #78350f;
    line-height: 1.8;
    margin: 0;
}

/* ===== ACCURACY FEEDBACK ===== */
.feedback-container {
    padding: 1.5rem;
    text-align: center;
}

.feedback-container > p {
    font-size: 1.1rem;
    color: #374151;
    margin-bottom: 1.5rem;
}

.accuracy-options {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.accuracy-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1.5rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 130px;
}

.accuracy-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.accuracy-btn .material-icons {
    font-size: 2.5rem;
}

.accuracy-btn span:last-child {
    font-weight: 600;
    font-size: 0.9rem;
}

.accuracy-btn[onclick*="very-accurate"] .material-icons { color: #22c55e; }
.accuracy-btn[onclick*="very-accurate"]:hover { border-color: #22c55e; background: #f0fdf4; }

.accuracy-btn[onclick*="'accurate'"] .material-icons { color: #3b82f6; }
.accuracy-btn[onclick*="'accurate'"]:hover { border-color: #3b82f6; background: #eff6ff; }

.accuracy-btn[onclick*="somewhat"] .material-icons { color: #f59e0b; }
.accuracy-btn[onclick*="somewhat"]:hover { border-color: #f59e0b; background: #fffbeb; }

.accuracy-btn[onclick*="inaccurate"] .material-icons { color: #ef4444; }
.accuracy-btn[onclick*="inaccurate"]:hover { border-color: #ef4444; background: #fef2f2; }

.feedback-stats {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 1rem 1.5rem;
    border-radius: 12px;
    display: inline-block;
}

.feedback-stats p {
    margin: 0;
    color: #0369a1;
}

.feedback-stats strong {
    color: #0284c7;
}


    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.accuracy-btn.selected {
    border-color: var(--primary-color);
    background: rgba(39, 174, 96, 0.1);
}

.accuracy-btn .material-icons {
    font-size: 2rem;
}

.accuracy-btn[onclick*="very-accurate"] .material-icons { color: #27ae60; }
.accuracy-btn[onclick*="'accurate'"] .material-icons { color: #3498db; }
.accuracy-btn[onclick*="neutral"] .material-icons { color: #f39c12; }
.accuracy-btn[onclick*="inaccurate"] .material-icons { color: #e74c3c; }

.feedback-stats {
    display: flex;
    gap: 3rem;
    justify-content: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.feedback-stat {
    text-align: center;
}

.feedback-stat strong {
    display: block;
    font-size: 1.75rem;
    color: var(--primary-color);
}

.feedback-stat small {
    color: #666;
}

/* ===== RESPONSIVE FOR NEW FEATURES ===== */
@media (max-width: 900px) {
    .sun-moon-grid {
        grid-template-columns: 1fr;
    }
    
    .comparison-selectors {
        flex-direction: column;
    }
    
    .comparison-select {
        max-width: 100%;
        width: 100%;
    }
    
    .comparison-row {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .comparison-row .comparison-metric {
        grid-column: span 2;
        margin-bottom: 0.5rem;
    }
    
    .planting-days-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .historical-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .export-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .golden-hour-info {
        grid-template-columns: 1fr;
    }
    
    /* New features responsive */
    .advanced-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .pest-risk-container {
        grid-template-columns: 1fr;
    }
    
    .risk-summary {
        flex-direction: row;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .risk-circle {
        width: 150px;
        height: 150px;
    }
    
    .risk-details {
        text-align: left;
        max-width: 300px;
    }
    
    .irrigation-container {
        grid-template-columns: 1fr;
    }
    
    .season-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .schedule-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    /* Monsoon & Season Tracker responsive */
    .monsoon-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .season-segment span {
        font-size: 0.75rem;
    }
    
    .season-segment small {
        display: none;
    }
    
    /* Voice report responsive */
    .voice-settings {
        flex-direction: column;
        gap: 1rem;
    }
    
    /* Feedback responsive */
    .accuracy-options {
        flex-direction: column;
        align-items: center;
    }
    
    .accuracy-btn {
        width: 100%;
        max-width: 200px;
    }
}

@media (max-width: 600px) {
    .planting-days-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .alert-types-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .method-options {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .historical-stats {
        grid-template-columns: 1fr;
    }
    
    /* New features responsive */
    .advanced-metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .voice-controls {
        flex-wrap: wrap;
    }
    
    .voice-btn.play-btn {
        width: 70px;
        height: 70px;
    }
    
    .voice-btn {
        width: 50px;
        height: 50px;
    }
    
    .accuracy-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .feedback-stats {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .season-info-grid {
        grid-template-columns: 1fr;
    }
    
    .schedule-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .irrigation-results {
        grid-template-columns: 1fr;
    }
    
    .irrigation-stat {
        padding: 1rem;
    }
    
    .irrigation-stat .material-icons {
        font-size: 1.8rem;
        padding: 0.4rem;
    }
    
    .irrigation-stat strong {
        font-size: 1.2rem;
    }
    
    .task-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    /* Monsoon tracker mobile */
    .monsoon-stats {
        grid-template-columns: 1fr;
    }
    
    .monsoon-stat-card {
        padding: 1rem;
    }
    
    .timeline-bar {
        height: 50px;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
