<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Service.php';
require_once '../models/Ticket.php';

$database = new Database();
$db = $database->getConnection();

// Get currently serving tickets
$serving_query = "SELECT t.ticket_number, s.display_name as service_name, s.prefix, d.desk_name, d.desk_number, t.called_at, t.status
                  FROM tickets t
                  JOIN services s ON t.service_id = s.id
                  LEFT JOIN desks d ON t.assigned_desk_id = d.id
                  WHERE t.status IN ('called', 'in_progress')
                  ORDER BY t.called_at DESC";
$serving_stmt = $db->prepare($serving_query);
$serving_stmt->execute();
$serving_tickets = $serving_stmt->fetchAll();

// Get queue overview
$service = new Service($db);
$services_result = $service->get_queue_status();
$services_data = [];
while ($row = $services_result->fetch()) {
    $services_data[] = $row;
}

// Get recent completed tickets
$recent_completed_query = "SELECT t.ticket_number, s.display_name as service_name, s.prefix, d.desk_name, t.completed_at
                          FROM tickets t
                          JOIN services s ON t.service_id = s.id
                          LEFT JOIN desks d ON t.assigned_desk_id = d.id
                          WHERE t.status = 'completed' AND DATE(t.completed_at) = CURDATE()
                          ORDER BY t.completed_at DESC LIMIT 5";
$recent_completed_stmt = $db->prepare($recent_completed_query);
$recent_completed_stmt->execute();
$recent_completed = $recent_completed_stmt->fetchAll();

// Get next to be called
$next_tickets_query = "SELECT t.ticket_number, s.display_name as service_name, s.prefix, t.generated_at
                      FROM tickets t
                      JOIN services s ON t.service_id = s.id
                      WHERE t.status = 'waiting'
                      ORDER BY t.priority_level DESC, t.queue_position ASC LIMIT 5";
$next_tickets_stmt = $db->prepare($next_tickets_query);
$next_tickets_stmt->execute();
$next_tickets = $next_tickets_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Queue Display Screen - Queue Management System</title>
    <link rel="stylesheet" href="../css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Custom styles for large display screens */
        .display-screen {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .ticker-scroll {
            animation: scroll-left 30s linear infinite;
        }
        
        @keyframes scroll-left {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes pulse-glow {
            from { box-shadow: 0 0 20px rgba(37, 99, 235, 0.3); }
            to { box-shadow: 0 0 30px rgba(37, 99, 235, 0.6); }
        }
        
        .serving-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .serving-card.active {
            border-color: #10b981;
            background: linear-gradient(145deg, #ecfdf5, #f0fdf4);
        }
        
        .ticket-number {
            font-size: 4rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 1024px) {
            .ticket-number {
                font-size: 3rem;
            }
        }
        
        @media (max-width: 768px) {
            .ticket-number {
                font-size: 2.5rem;
            }
        }
    </style>
<script type="module" src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fqueuemana2356back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.8"></script>
</head>
<body class="display-screen">
    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-sm shadow-lg border-b-4 border-primary sticky top-0 z-50">
        <div class="px-8 py-6">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-6">
                    <div class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center pulse-glow">
                        <i class="fas fa-tv text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-text-primary mb-1">Queue Display</h1>
                        <p class="text-xl text-secondary-600">Real-time Service Status</p>
                    </div>
                </div>

                <!-- Current Time and Status -->
                <div class="text-right">
                    <div class="text-3xl font-bold text-text-primary mb-1" id="currentTime">2:45:30 PM</div>
                    <div class="text-lg text-secondary-600">December 24, 2025</div>
                    <div class="flex items-center justify-end mt-2">
                        <div class="w-3 h-3 bg-success rounded-full animate-pulse mr-2"></div>
                        <span class="text-success font-medium">System Online</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="bg-white/90 backdrop-blur-sm shadow-md border-b border-secondary-200">
        <div class="px-8 py-4">
            <div class="flex items-center justify-center space-x-8">
                <a href="admin_dashboard.html" class="flex items-center px-6 py-3 text-secondary-600 hover:text-primary hover:bg-primary-50 rounded-lg transition-smooth">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span class="font-medium">Admin Dashboard</span>
                </a>
                <a href="operator_dashboard.html" class="flex items-center px-6 py-3 text-secondary-600 hover:text-primary hover:bg-primary-50 rounded-lg transition-smooth">
                    <i class="fas fa-headset mr-3"></i>
                    <span class="font-medium">Operator Dashboard</span>
                </a>
                <a href="customer_ticket_generation.html" class="flex items-center px-6 py-3 text-secondary-600 hover:text-primary hover:bg-primary-50 rounded-lg transition-smooth">
                    <i class="fas fa-ticket-alt mr-3"></i>
                    <span class="font-medium">Get Ticket</span>
                </a>
                <a href="queue_display_screen.html" class="flex items-center px-6 py-3 text-primary bg-primary-50 rounded-lg font-medium">
                    <i class="fas fa-tv mr-3"></i>
                    <span>Queue Display</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Scrolling Ticker -->
    <section class="bg-primary text-white py-4 overflow-hidden">
        <div class="ticker-scroll whitespace-nowrap">
            <span class="text-2xl font-semibold mx-8">
                <i class="fas fa-bullhorn mr-3"></i>
                Recently Called: A045, B023, C012, A046, B024 • Next Expected: A047, B025, C013 • 
                Average Wait Time: 8 minutes • Total Served Today: 247 tickets • 
                Welcome to our service center - Thank you for your patience
            </span>
        </div>
    </section>

    <!-- Main Display Area -->
    <main class="px-8 py-8">
        <!-- Currently Serving Section -->
        <section class="mb-12">
            <div class="text-center mb-8">
                <h2 class="text-5xl font-bold text-white mb-4">Currently Serving</h2>
                <div class="w-32 h-1 bg-white/50 mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-8">
                <?php if (count($serving_tickets) > 0): ?>
                    <?php foreach ($serving_tickets as $ticket): ?>
                        <div class="serving-card active rounded-2xl p-8 text-center">
                            <div class="mb-6">
                                <div class="w-16 h-16 bg-success rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-desktop text-white text-2xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-text-primary mb-2"><?php echo htmlspecialchars($ticket['desk_name'] ?? 'Desktop ' . $ticket['desk_number']); ?></h3>
                                <p class="text-lg text-secondary-600"><?php echo htmlspecialchars($ticket['service_name']); ?></p>
                            </div>
                            
                            <div class="ticket-number text-success mb-4"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                            
                            <div class="status-indicator status-success text-lg mb-4">
                                <i class="fas fa-circle text-sm mr-2"></i>
                                Now Serving
                            </div>
                            
                            <div class="text-secondary-600">
                                <p class="text-lg">Started: <?php echo date('g:i A', strtotime($ticket['called_at'])); ?></p>
                                <p class="text-base mt-2">Service in progress</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Fill remaining slots with available desks -->
                <?php 
                $remaining_slots = 4 - count($serving_tickets);
                for ($i = 0; $i < $remaining_slots; $i++): 
                ?>
                    <div class="serving-card rounded-2xl p-8 text-center">
                        <div class="mb-6">
                            <div class="w-16 h-16 bg-secondary-300 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-desktop text-secondary-600 text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-text-primary mb-2">Desktop <?php echo count($serving_tickets) + $i + 1; ?></h3>
                            <p class="text-lg text-secondary-600">Available</p>
                        </div>
                        
                        <div class="ticket-number text-secondary-400 mb-4">---</div>
                        
                        <div class="status-indicator bg-secondary-200 text-secondary-600 text-lg mb-4">
                            <i class="fas fa-circle text-sm mr-2"></i>
                            Available
                        </div>
                        
                        <div class="text-secondary-600">
                            <p class="text-lg">Ready for next customer</p>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- Queue Status Overview -->
        <section class="mb-12">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-8 shadow-xl">
                <h3 class="text-3xl font-bold text-text-primary mb-8 text-center">Queue Overview</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($services_data as $service): ?>
                        <div class="text-center p-6 bg-primary-50 rounded-xl">
                            <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center mx-auto mb-4">
                                <i class="<?php echo $service['icon_class'] ?? 'fas fa-cog'; ?> text-white"></i>
                            </div>
                            <h4 class="text-xl font-semibold text-text-primary mb-2"><?php echo htmlspecialchars($service['display_name']); ?></h4>
                            <div class="text-3xl font-bold text-primary mb-2"><?php echo $service['current_queue_count'] ?? 0; ?></div>
                            <p class="text-secondary-600">customers waiting</p>
                            <p class="text-sm text-secondary-500 mt-2">Est. wait: <?php echo $service['average_wait_time'] ?? $service['estimated_duration']; ?> min</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="mb-8">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-8 shadow-xl">
                <h3 class="text-3xl font-bold text-text-primary mb-8 text-center">Recent Activity</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recently Completed -->
                    <div>
                        <h4 class="text-xl font-semibold text-text-primary mb-4 flex items-center">
                            <i class="fas fa-check-circle text-success mr-3"></i>
                            Recently Completed
                        </h4>
                        <div class="space-y-3">
                            <?php if (count($recent_completed) > 0): ?>
                                <?php foreach ($recent_completed as $ticket): ?>
                                    <div class="flex items-center justify-between p-4 bg-success-50 rounded-lg">
                                        <div class="flex items-center space-x-4">
                                            <div class="text-2xl font-bold text-success"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                            <div>
                                                <p class="font-medium text-text-primary"><?php echo htmlspecialchars($ticket['service_name']); ?></p>
                                                <p class="text-sm text-secondary-500"><?php echo htmlspecialchars($ticket['desk_name'] ?? 'Desktop'); ?> • Completed at <?php echo date('g:i A', strtotime($ticket['completed_at'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-success">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-secondary-500">
                                    <i class="fas fa-clock text-4xl mb-4"></i>
                                    <p>No completed tickets today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Next to be Called -->
                    <div>
                        <h4 class="text-xl font-semibold text-text-primary mb-4 flex items-center">
                            <i class="fas fa-clock text-primary mr-3"></i>
                            Next to be Called
                        </h4>
                        <div class="space-y-3">
                            <?php if (count($next_tickets) > 0): ?>
                                <?php foreach ($next_tickets as $ticket): ?>
                                    <div class="flex items-center justify-between p-4 bg-primary-50 rounded-lg">
                                        <div class="flex items-center space-x-4">
                                            <div class="text-2xl font-bold text-primary"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                            <div>
                                                <p class="font-medium text-text-primary"><?php echo htmlspecialchars($ticket['service_name']); ?></p>
                                                <p class="text-sm text-secondary-500">Waiting since <?php echo date('g:i A', strtotime($ticket['generated_at'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-hourglass-half text-xl"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-secondary-500">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p>No tickets waiting</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white/95 backdrop-blur-sm border-t border-secondary-200 py-6">
        <div class="px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-success rounded-full animate-pulse"></div>
                        <span class="text-success font-medium">Live Updates Active</span>
                    </div>
                    <div class="text-secondary-500">
                        Last updated: <span id="lastUpdated">2:45:30 PM</span>
                    </div>
                </div>
                
                <div class="text-secondary-500">
                    © 2025 Queue Management System. All Rights Reserved.
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Real-time clock update
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('lastUpdated').textContent = timeString;
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call

        // Simulate real-time queue updates
        function simulateQueueUpdates() {
            // Fetch real-time queue status
            fetch('../api/get_queue_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Queue status updated at:', new Date().toLocaleTimeString());
                        // Refresh page to show updated data
                        location.reload();
                    }
                })
                .catch(error => console.error('Error fetching queue status:', error));
        }

        // Simulate updates every 30 seconds
        setInterval(simulateQueueUpdates, 30000);

        // Add visual feedback for active serving cards
        function animateServingCards() {
            const activeCards = document.querySelectorAll('.serving-card.active');
            activeCards.forEach(card => {
                card.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    card.style.transform = 'scale(1)';
                }, 1000);
            });
        }

        // Animate serving cards every 5 seconds
        setInterval(animateServingCards, 5000);

        // Initialize display optimizations
        document.addEventListener('DOMContentLoaded', function() {
            // Optimize for large displays
            if (window.innerWidth > 1920) {
                document.body.style.fontSize = '1.2em';
            }
            
            // Handle orientation changes
            window.addEventListener('orientationchange', function() {
                setTimeout(() => {
                    location.reload();
                }, 500);
            });
        });

        // Keyboard shortcuts for display control
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'F11':
                    // Toggle fullscreen
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                    break;
                case 'r':
                case 'R':
                    // Refresh display
                    location.reload();
                    break;
            }
        });
    </script>
<script id="dhws-dataInjector" src="../public/dhws-data-injector.js"></script>
</body>
</html>