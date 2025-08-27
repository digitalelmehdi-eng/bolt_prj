<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Service.php';
require_once '../includes/functions.php';

// Get services and queue status
$database = new Database();
$db = $database->getConnection();

$service = new Service($db);
$services_result = $service->get_queue_status();
$services_data = [];
while ($row = $services_result->fetch()) {
    $services_data[] = $row;
}

// Get total people in queue
$total_queue_query = "SELECT COUNT(*) as total FROM tickets WHERE status = 'waiting'";
$total_queue_stmt = $db->prepare($total_queue_query);
$total_queue_stmt->execute();
$total_in_queue = $total_queue_stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Select Service - Queue Management System</title>
    <link rel="stylesheet" href="../css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<script type="module" src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fqueuemana2356back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.8"></script>
</head>
<body class="bg-background min-h-screen">
    <!-- Header -->
    <header class="bg-surface shadow-base border-b border-secondary-200 sticky top-0 z-50">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                        <i class="fas fa-ticket-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-semibold text-text-primary">Queue Management</h1>
                        <p class="text-sm text-secondary-500">Select Your Service</p>
                    </div>
                </div>

                <!-- Current Time and Status -->
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-lg font-semibold text-text-primary" id="currentTime">2:24 PM</p>
                        <p class="text-sm text-secondary-500">August 24, 2025</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-success rounded-full animate-pulse"></div>
                        <span class="text-sm text-success font-medium">System Online</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <!-- Welcome Section -->
        <div class="text-center mb-8">
            <h2 class="text-4xl font-semibold text-text-primary mb-4">Welcome!</h2>
            <p class="text-xl text-secondary-600 max-w-2xl mx-auto">Please select the service you need. Your ticket will be generated instantly with an estimated waiting time.</p>
        </div>

        <!-- Current Queue Status Banner -->
        <div class="bg-primary-50 rounded-lg p-6 mb-8 border border-primary-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                        <i class="fas fa-info-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-primary">Current Queue Status</h3>
                        <p class="text-sm text-primary-600">Live updates • Last updated: 30 seconds ago</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-semibold text-primary">47</p>
                    <p class="text-2xl font-semibold text-primary"><?php echo $total_in_queue; ?></p>
                    <p class="text-sm text-primary-600">People in queue</p>
                </div>
            </div>
        </div>

        <!-- Service Selection Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($services_data as $service_item): ?>
                <div class="card p-8 hover:shadow-lg transition-smooth cursor-pointer touch-target" onclick="selectService(<?php echo $service_item['id']; ?>, '<?php echo $service_item['prefix']; ?>')">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="<?php echo $service_item['icon_class'] ?? 'fas fa-cog'; ?> text-primary text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-semibold text-text-primary mb-2"><?php echo htmlspecialchars($service_item['display_name']); ?></h3>
                        <p class="text-secondary-600 mb-4"><?php echo htmlspecialchars($service_item['description'] ?? 'Service description'); ?></p>
                        
                        <!-- Queue Info -->
                        <div class="bg-secondary-50 rounded-lg p-4 mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-secondary-600">Currently Serving:</span>
                                <span class="text-lg font-semibold text-primary"><?php echo $service_item['current_serving_number'] ?? '---'; ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-secondary-600">People Waiting:</span>
                                <span class="text-lg font-semibold text-warning"><?php echo $service_item['current_queue_count'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-secondary-600">Est. Wait Time:</span>
                                <span class="text-lg font-semibold text-success"><?php echo $service_item['average_wait_time'] ?? $service_item['estimated_duration']; ?> min</span>
                            </div>
                        </div>

                        <button class="w-full btn btn-primary text-lg py-4">
                            <i class="fas fa-plus mr-2"></i>
                            <?php echo $service_item['is_priority'] ? 'Get Priority Ticket' : 'Get Ticket'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Help Section -->
        <div class="bg-surface rounded-lg p-6 shadow-base">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-secondary-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-question-circle text-secondary-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-text-primary">Need Help?</h3>
                        <p class="text-secondary-600">Our staff is here to assist you</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button class="btn btn-secondary" onclick="callStaff()">
                        <i class="fas fa-bell mr-2"></i>
                        Call Staff
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.href='queue_display_screen.html'">
                        <i class="fas fa-tv mr-2"></i>
                        View Queue Display
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Ticket Generation Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-surface rounded-lg max-w-md w-full p-8 text-center">
            <div class="w-20 h-20 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-success text-3xl"></i>
            </div>
            
            <h3 class="text-2xl font-semibold text-text-primary mb-4">Ticket Generated Successfully!</h3>
            
            <div class="bg-primary-50 rounded-lg p-6 mb-6 border-2 border-dashed border-primary">
                <p class="text-sm text-secondary-600 mb-2">Your Ticket Number</p>
                <p class="text-4xl font-semibold text-primary mb-4" id="ticketNumber">A046</p>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary-600">Service:</span>
                        <span class="text-sm font-medium text-text-primary" id="serviceName">General Service</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary-600">Queue Position:</span>
                        <span class="text-sm font-medium text-text-primary" id="queuePosition">13</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary-600">Est. Wait Time:</span>
                        <span class="text-sm font-medium text-success" id="waitTime">8-12 minutes</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary-600">Time Generated:</span>
                        <span class="text-sm font-medium text-text-primary" id="generatedTime">2:24 PM</span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <button class="w-full btn btn-primary" onclick="printTicket()">
                    <i class="fas fa-print mr-2"></i>
                    Print Ticket
                </button>
                <button class="w-full btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times mr-2"></i>
                    Close
                </button>
                <button class="w-full btn btn-secondary" onclick="generateAnother()">
                    <i class="fas fa-plus mr-2"></i>
                    Generate Another Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed top-4 right-4 bg-success text-white px-6 py-4 rounded-lg shadow-lg z-50 hidden">
        <div class="flex items-center space-x-3">
            <i class="fas fa-check-circle"></i>
            <span>Ticket printed successfully!</span>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeString;
        }

        // Update time every minute
        setInterval(updateTime, 60000);
        updateTime();

        // Select service and generate ticket
        function selectService(serviceId, prefix) {
            // Call API to generate ticket
            fetch('../api/generate_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    priority_level: 'normal'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update modal content with real data
                    document.getElementById('ticketNumber').textContent = data.ticket.ticket_number;
                    document.getElementById('serviceName').textContent = data.ticket.service_name;
                    document.getElementById('queuePosition').textContent = data.ticket.queue_position;
                    document.getElementById('waitTime').textContent = data.ticket.estimated_wait_time + ' minutes';
                    document.getElementById('generatedTime').textContent = new Date().toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true 
                    });
                    
                    // Show modal
                    document.getElementById('ticketModal').classList.remove('hidden');
                } else {
                    alert('Error generating ticket: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error generating ticket. Please try again.');
            });
        }

        // Print ticket function
        function printTicket() {
            // Simulate printing process
            const ticketNumber = document.getElementById('ticketNumber').textContent;
            const serviceName = document.getElementById('serviceName').textContent;
            
            // In real implementation, this would integrate with QZ Tray
            console.log('Printing ticket:', ticketNumber, 'for', serviceName);
            
            // Show success toast
            showSuccessToast();
            
            // Close modal after short delay
            setTimeout(() => {
                closeModal();
            }, 2000);
        }

        // Show success toast
        function showSuccessToast() {
            const toast = document.getElementById('successToast');
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        // Close modal
        function closeModal() {
            document.getElementById('ticketModal').classList.add('hidden');
        }

        // Generate another ticket
        function generateAnother() {
            closeModal();
        }

        // Call staff function
        function callStaff() {
            alert('Staff has been notified and will assist you shortly.');
        }

        // Real-time queue updates simulation
        setInterval(() => {
            // Fetch real-time queue status
            fetch('../api/get_queue_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateQueueDisplay(data);
                    }
                })
                .catch(error => console.error('Error fetching queue status:', error));
        }, 30000);

        function updateQueueDisplay(data) {
            // Update queue counts and serving numbers
            console.log('Queue updated at:', new Date().toLocaleTimeString());
            // Refresh the page to show updated data
            location.reload();
        }

        // Touch feedback for mobile devices
        document.addEventListener('touchstart', function(e) {
            if (e.target.closest('.touch-target')) {
                e.target.closest('.touch-target').style.transform = 'scale(0.98)';
            }
        });

        document.addEventListener('touchend', function(e) {
            if (e.target.closest('.touch-target')) {
                setTimeout(() => {
                    e.target.closest('.touch-target').style.transform = 'scale(1)';
                }, 100);
            }
        });

        // Accessibility: Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
<script id="dhws-dataInjector" src="../public/dhws-data-injector.js"></script>
</body>
</html>