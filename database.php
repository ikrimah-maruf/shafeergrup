<?php
class Database {
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // TOUR PACKAGES
    public function getTourPackages() {
        $sql = "SELECT * FROM tour_packages ORDER BY id DESC";
        return $this->conn->query($sql);
    }
    
    public function getTourPackage($id) {
        $stmt = $this->conn->prepare("SELECT * FROM tour_packages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // GET VEHICLE OPTIONS FOR A TOUR PACKAGE
    public function getVehicleOptions($tour_id) {
        $stmt = $this->conn->prepare("SELECT * FROM tour_package_options WHERE tour_package_id = ? ORDER BY price");
        $stmt->bind_param("i", $tour_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // DRIVING SCHOOL REGISTRATION
    public function saveRegistration($data) {
        $stmt = $this->conn->prepare("INSERT INTO driving_registrations (name, phone, course_type, preferred_schedule, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $data['name'],
            $data['phone'], 
            $data['course_type'],
            $data['preferred_schedule'],
            $data['message']
        );
        return $stmt->execute();
    }
}
?>