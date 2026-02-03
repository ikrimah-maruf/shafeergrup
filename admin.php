<?php
session_start();
require_once 'config.php';
require_once 'database.php';

$db = new Database();

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_tour'])) {
        // Add tour package
        $title = $_POST['title'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        
        // Save to database
        $stmt = $db->getConnection()->prepare("INSERT INTO tour_packages (title_id, description_id, duration) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $description, $duration);
        $stmt->execute();
        
        $tour_id = $stmt->insert_id;
        
        // Add vehicle options
        $vehicles = $_POST['vehicles'];
        foreach ($vehicles as $vehicle) {
            $stmt = $db->getConnection()->prepare("INSERT INTO tour_package_options (tour_package_id, vehicle_name, max_person, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isid", $tour_id, $vehicle['name'], $vehicle['max_person'], $vehicle['price']);
            $stmt->execute();
        }
        
        echo "<script>alert('Paket tour berhasil ditambahkan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - SHAFEER GRUP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f3f4f6; }
        
        .sidebar { width: 250px; background: #1e40af; color: white; height: 100vh; position: fixed; }
        .sidebar-header { padding: 1rem; background: #1e3a8a; }
        .sidebar-menu { padding: 1rem; }
        .menu-item { padding: 0.5rem; margin-bottom: 0.5rem; cursor: pointer; }
        .menu-item:hover { background: #3b82f6; }
        
        .main-content { margin-left: 250px; padding: 2rem; }
        
        .card { background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.5rem; border: 1px solid #ddd; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🚗 SHAFEER ADMIN</h2>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item" onclick="showSection('dashboard')">📊 Dashboard</div>
            <div class="menu-item" onclick="showSection('tour')">🏝️ Tour Packages</div>
            <div class="menu-item" onclick="showSection('registrations')">📝 Driving School Registrations</div>
            <div class="menu-item" onclick="showSection('add-tour')">➕ Add Tour Package</div>
            <div class="menu-item" onclick="logout()">🚪 Logout</div>
        </div>
    </div>
    
    <div class="main-content">
        <!-- Dashboard -->
        <div id="dashboard" class="section">
            <h1>Dashboard</h1>
            <div class="card">
                <h3>📊 Statistics</h3>
                <?php
                $tours = $db->getTourPackages();
                $tour_count = $tours->num_rows;
                
                $regs = $db->getConnection()->query("SELECT COUNT(*) as total FROM driving_registrations");
                $reg_count = $regs->fetch_assoc()['total'];
                ?>
                <p>Total Tour Packages: <strong><?php echo $tour_count; ?></strong></p>
                <p>Total Registrations: <strong><?php echo $reg_count; ?></strong></p>
            </div>
        </div>
        
        <!-- Tour Packages -->
        <div id="tour" class="section" style="display:none;">
            <h1>Tour Packages</h1>
            <div class="card">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Package Name</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                    <?php while($tour = $tours->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $tour['id']; ?></td>
                        <td><?php echo $tour['title_id']; ?></td>
                        <td><?php echo $tour['duration']; ?></td>
                        <td>
                            <button onclick="editTour(<?php echo $tour['id']; ?>)">Edit</button>
                            <button onclick="deleteTour(<?php echo $tour['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        
        <!-- Registrations -->
        <div id="registrations" class="section" style="display:none;">
            <h1>Driving School Registrations</h1>
            <div class="card">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Course Type</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    <?php
                    $registrations = $db->getConnection()->query("SELECT * FROM driving_registrations ORDER BY created_at DESC");
                    while($reg = $registrations->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $reg['id']; ?></td>
                        <td><?php echo $reg['name']; ?></td>
                        <td><?php echo $reg['phone']; ?></td>
                        <td><?php echo $reg['course_type']; ?></td>
                        <td>
                            <select onchange="updateStatus(<?php echo $reg['id']; ?>, this.value)">
                                <option value="pending" <?php echo $reg['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $reg['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="rejected" <?php echo $reg['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </td>
                        <td><?php echo $reg['created_at']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        
        <!-- Add Tour Package -->
        <div id="add-tour" class="section" style="display:none;">
            <h1>Add Tour Package</h1>
            <div class="card">
                <form method="POST">
                    <div style="margin-bottom: 1rem;">
                        <label>Package Title:</label><br>
                        <input type="text" name="title" required style="width:100%; padding:0.5rem;">
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label>Description:</label><br>
                        <textarea name="description" rows="3" style="width:100%; padding:0.5rem;"></textarea>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label>Duration:</label><br>
                        <input type="text" name="duration" placeholder="2 Hari 1 Malam" style="width:100%; padding:0.5rem;">
                    </div>
                    
                    <h3>Vehicle Options</h3>
                    <div id="vehicle-container">
                        <div class="vehicle-option" style="margin-bottom: 1rem; padding:1rem; border:1px solid #ddd;">
                            <label>Vehicle Name:</label>
                            <input type="text" name="vehicles[0][name]" placeholder="Avanza" required>
                            
                            <label>Max Person:</label>
                            <input type="number" name="vehicles[0][max_person]" placeholder="4" required>
                            
                            <label>Price:</label>
                            <input type="number" name="vehicles[0][price]" placeholder="1200000" required>
                        </div>
                    </div>
                    
                    <button type="button" onclick="addVehicle()">➕ Add Another Vehicle</button>
                    
                    <br><br>
                    <button type="submit" name="add_tour" style="background:#10b981; color:white; padding:0.5rem 1rem; border:none; border-radius:5px;">
                        💾 Save Tour Package
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionId).style.display = 'block';
        }
        
        let vehicleCount = 1;
        function addVehicle() {
            const container = document.getElementById('vehicle-container');
            const newVehicle = document.createElement('div');
            newVehicle.className = 'vehicle-option';
            newVehicle.style = 'margin-bottom: 1rem; padding:1rem; border:1px solid #ddd;';
            newVehicle.innerHTML = `
                <label>Vehicle Name:</label>
                <input type="text" name="vehicles[${vehicleCount}][name]" placeholder="Elf" required>
                
                <label>Max Person:</label>
                <input type="number" name="vehicles[${vehicleCount}][max_person]" placeholder="12" required>
                
                <label>Price:</label>
                <input type="number" name="vehicles[${vehicleCount}][price]" placeholder="2500000" required>
                
                <button type="button" onclick="this.parentElement.remove()" style="background:#ef4444; color:white; padding:0.25rem 0.5rem; border:none; border-radius:3px; margin-left:1rem;">
                    Remove
                </button>
            `;
            container.appendChild(newVehicle);
            vehicleCount++;
        }
        
        function updateStatus(id, status) {
            // Send AJAX request to update status
            fetch('update_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, status: status})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully!');
                }
            });
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // Show dashboard by default
        showSection('dashboard');
    </script>
</body>
</html>