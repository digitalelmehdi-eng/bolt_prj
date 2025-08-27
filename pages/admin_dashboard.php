@@ .. @@
+<?php
+require_once '../config/config.php';
+require_once '../config/database.php';
+require_once '../models/Service.php';
+require_once '../models/Ticket.php';
+require_once '../includes/functions.php';
+
+// Check authentication
+check_authentication();
+
+// Check admin permissions
+if (!has_permission('admin')) {
+    header('Location: ../login.php');
+    exit();
+}
+
+// Get dashboard data
+$database = new Database();
+$db = $database->getConnection();
+
+// Get today's statistics
+$today_tickets_query = "SELECT COUNT(*) as total FROM tickets WHERE DATE(generated_at) = CURDATE()";
+$today_tickets_stmt = $db->prepare($today_tickets_query);
+$today_tickets_stmt->execute();
+$today_tickets = $today_tickets_stmt->fetch()['total'];
+
+// Get average wait time
+$avg_wait_query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, generated_at, called_at)) as avg_wait 
+                   FROM tickets WHERE called_at IS NOT NULL AND DATE(generated_at) = CURDATE()";
+$avg_wait_stmt = $db->prepare($avg_wait_query);
+$avg_wait_stmt->execute();
+$avg_wait = round($avg_wait_stmt->fetch()['avg_wait'] ?? 0, 1);
+
+// Get average serving time
+$avg_serve_query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as avg_serve 
+                    FROM tickets WHERE completed_at IS NOT NULL AND DATE(generated_at) = CURDATE()";
+$avg_serve_stmt = $db->prepare($avg_serve_query);
+$avg_serve_stmt->execute();
+$avg_serve = round($avg_serve_stmt->fetch()['avg_serve'] ?? 0, 1);
+
+// Get active desks
+$active_desks_query = "SELECT COUNT(*) as active FROM desks WHERE status = 'active'";
+$active_desks_stmt = $db->prepare($active_desks_query);
+$active_desks_stmt->execute();
+$active_desks = $active_desks_stmt->fetch()['active'];
+
+$total_desks_query = "SELECT COUNT(*) as total FROM desks";
+$total_desks_stmt = $db->prepare($total_desks_query);
+$total_desks_stmt->execute();
+$total_desks = $total_desks_stmt->fetch()['total'];
+
+// Get currently serving tickets
+$serving_query = "SELECT t.ticket_number, s.display_name as service_name, s.prefix, d.desk_name, t.called_at
+                  FROM tickets t
+                  JOIN services s ON t.service_id = s.id
+                  LEFT JOIN desks d ON t.assigned_desk_id = d.id
+                  WHERE t.status IN ('called', 'in_progress')
+                  ORDER BY t.called_at DESC";
+$serving_stmt = $db->prepare($serving_query);
+$serving_stmt->execute();
+$serving_tickets = $serving_stmt->fetchAll();
+?>
 <!DOCTYPE html>
 <html lang="en">
 <head>
@@ .. @@
                     <!-- User Profile -->
                     <div class="flex items-center space-x-3">
                         <div class="text-right hidden sm:block">
-                            <p class="text-sm font-medium text-text-primary">Sarah Johnson</p>
-                            <p class="text-xs text-secondary-500">System Administrator</p>
+                            <p class="text-sm font-medium text-text-primary"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
+                            <p class="text-xs text-secondary-500"><?php echo ucfirst($_SESSION['role']); ?></p>
                         </div>
                         <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                             <i class="fas fa-user text-primary"></i>
                         </div>
-                        <button class="p-2 rounded-lg hover:bg-secondary-100 transition-smooth">
+                        <a href="../logout.php" class="p-2 rounded-lg hover:bg-secondary-100 transition-smooth">
                             <i class="fas fa-sign-out-alt text-secondary-600"></i>
-                        </button>
+                        </a>
                     </div>
                 </div>
             </div>
@@ .. @@
                             <div>
-                                <p class="text-3xl font-semibold text-text-primary">247</p>
+                                <p class="text-3xl font-semibold text-text-primary"><?php echo $today_tickets; ?></p>
                                 <div class="flex items-center mt-2">
                                     <i class="fas fa-arrow-up text-success text-sm mr-1"></i>
                                     <span class="text-sm text-success">+12.5%</span>
@@ .. @@
                             <div>
-                                <p class="text-3xl font-semibold text-text-primary">8.5<span class="text-lg text-secondary-500">min</span></p>
+                                <p class="text-3xl font-semibold text-text-primary"><?php echo $avg_wait; ?><span class="text-lg text-secondary-500">min</span></p>
                                 <div class="flex items-center mt-2">
                                     <i class="fas fa-arrow-down text-success text-sm mr-1"></i>
                                     <span class="text-sm text-success">-2.3min</span>
@@ .. @@
                             <div>
-                                <p class="text-3xl font-semibold text-text-primary">4.2<span class="text-lg text-secondary-500">min</span></p>
+                                <p class="text-3xl font-semibold text-text-primary"><?php echo $avg_serve; ?><span class="text-lg text-secondary-500">min</span></p>
                                 <div class="flex items-center mt-2">
                                     <i class="fas fa-arrow-up text-error text-sm mr-1"></i>
                                     <span class="text-sm text-error">+0.8min</span>
@@ .. @@
                             <div>
-                                <p class="text-3xl font-semibold text-text-primary">8<span class="text-lg text-secondary-500">/12</span></p>
+                                <p class="text-3xl font-semibold text-text-primary"><?php echo $active_desks; ?><span class="text-lg text-secondary-500">/<?php echo $total_desks; ?></span></p>
                                 <div class="flex items-center mt-2">
                                     <div class="w-2 h-2 bg-success rounded-full mr-2"></div>
                                     <span class="text-sm text-secondary-500">All systems operational</span>
@@ .. @@
                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                         <!-- Desktop Status Cards -->
-                        <div class="bg-success-100 rounded-lg p-4">
-                            <div class="flex items-center justify-between mb-2">
-                                <span class="text-sm font-medium text-success-600">Desktop 1</span>
-                                <div class="status-indicator status-success">
-                                    <i class="fas fa-circle text-xs mr-1"></i>
-                                    Serving
-                                </div>
-                            </div>
-                            <p class="text-lg font-semibold text-text-primary">A045</p>
-                            <p class="text-sm text-secondary-500">General Service</p>
-                            <div class="mt-2 text-xs text-secondary-500">
-                                <i class="fas fa-clock mr-1"></i>
-                                Started: 2:15 PM
-                            </div>
-                        </div>
-
-                        <div class="bg-primary-50 rounded-lg p-4">
-                            <div class="flex items-center justify-between mb-2">
-                                <span class="text-sm font-medium text-primary-600">Desktop 2</span>
-                                <div class="status-indicator status-success">
-                                    <i class="fas fa-circle text-xs mr-1"></i>
-                                    Serving
-                                </div>
-                            </div>
-                            <p class="text-lg font-semibold text-text-primary">B023</p>
-                            <p class="text-sm text-secondary-500">Account Opening</p>
-                            <div class="mt-2 text-xs text-secondary-500">
-                                <i class="fas fa-clock mr-1"></i>
-                                Started: 2:18 PM
-                            </div>
-                        </div>
-
-                        <div class="bg-secondary-100 rounded-lg p-4">
-                            <div class="flex items-center justify-between mb-2">
-                                <span class="text-sm font-medium text-secondary-600">Desktop 3</span>
-                                <div class="status-indicator bg-secondary-200 text-secondary-600">
-                                    <i class="fas fa-circle text-xs mr-1"></i>
-                                    Available
-                                </div>
-                            </div>
-                            <p class="text-lg font-semibold text-secondary-500">---</p>
-                            <p class="text-sm text-secondary-500">Loan Services</p>
-                            <div class="mt-2 text-xs text-secondary-500">
-                                <i class="fas fa-clock mr-1"></i>
-                                Ready for next
-                            </div>
-                        </div>
-
-                        <div class="bg-warning-100 rounded-lg p-4">
-                            <div class="flex items-center justify-between mb-2">
-                                <span class="text-sm font-medium text-warning-600">Desktop 4</span>
-                                <div class="status-indicator status-warning">
-                                    <i class="fas fa-circle text-xs mr-1"></i>
-                                    Break
-                                </div>
-                            </div>
-                            <p class="text-lg font-semibold text-secondary-500">---</p>
-                            <p class="text-sm text-secondary-500">Customer Support</p>
-                            <div class="mt-2 text-xs text-secondary-500">
-                                <i class="fas fa-clock mr-1"></i>
-                                Back at: 2:45 PM
-                            </div>
-                        </div>
+                        <?php if (count($serving_tickets) > 0): ?>
+                            <?php foreach ($serving_tickets as $index => $ticket): ?>
+                                <div class="bg-success-100 rounded-lg p-4">
+                                    <div class="flex items-center justify-between mb-2">
+                                        <span class="text-sm font-medium text-success-600"><?php echo htmlspecialchars($ticket['desk_name'] ?? 'Desktop ' . ($index + 1)); ?></span>
+                                        <div class="status-indicator status-success">
+                                            <i class="fas fa-circle text-xs mr-1"></i>
+                                            Serving
+                                        </div>
+                                    </div>
+                                    <p class="text-lg font-semibold text-text-primary"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
+                                    <p class="text-sm text-secondary-500"><?php echo htmlspecialchars($ticket['service_name']); ?></p>
+                                    <div class="mt-2 text-xs text-secondary-500">
+                                        <i class="fas fa-clock mr-1"></i>
+                                        Started: <?php echo date('g:i A', strtotime($ticket['called_at'])); ?>
+                                    </div>
+                                </div>
+                            <?php endforeach; ?>
+                        <?php else: ?>
+                            <div class="bg-secondary-100 rounded-lg p-4">
+                                <div class="flex items-center justify-between mb-2">
+                                    <span class="text-sm font-medium text-secondary-600">No Active Services</span>
+                                    <div class="status-indicator bg-secondary-200 text-secondary-600">
+                                        <i class="fas fa-circle text-xs mr-1"></i>
+                                        Available
+                                    </div>
+                                </div>
+                                <p class="text-lg font-semibold text-secondary-500">---</p>
+                                <p class="text-sm text-secondary-500">Ready for customers</p>
+                            </div>
+                        <?php endif; ?>
                     </div>
                 </div>
@@ .. @@
         // Real-time updates simulation
         setInterval(() => {
-            // Simulate real-time updates
-            const timestamp = new Date().toLocaleTimeString();
-            console.log('Queue status updated at:', timestamp);
+            // Fetch real-time queue status
+            fetch('../api/get_queue_status.php')
+                .then(response => response.json())
+                .then(data => {
+                    if (data.success) {
+                        updateDashboardStats(data);
+                    }
+                })
+                .catch(error => console.error('Error fetching queue status:', error));
         }, 30000);
+
+        function updateDashboardStats(data) {
+            // Update serving tickets display
+            console.log('Dashboard updated at:', new Date().toLocaleTimeString());
+            // You can add more specific updates here based on the API response
+        }
     </script>
 <script id="dhws-dataInjector" src="../public/dhws-data-injector.js"></script>
 </body>