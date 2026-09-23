<?php
class Queue extends Model {
    protected $table = 'queue';

    public function generateQueueNumber() {
        $prefix = 'Q';
        $sql = "SELECT MAX(CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)) as max_num 
                FROM queue WHERE DATE(created_at) = CURDATE()";
        $result = $this->query($sql);
        $nextNum = ($result[0]['max_num'] ?? 0) + 1;
        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    public function getActiveQueue() {
        $sql = "SELECT q.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                a.id as appointment_id
                FROM queue q
                JOIN appointments a ON q.appointment_id = a.id
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE q.status IN ('waiting', 'in_progress')
                AND DATE(q.created_at) = CURDATE()
                ORDER BY q.status DESC, q.id ASC";
        return $this->query($sql);
    }

    public function getTodayQueue() {
        return $this->getActiveQueue();
    }

    public function getCurrentServing() {
        $sql = "SELECT q.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name
                FROM queue q
                JOIN appointments a ON q.appointment_id = a.id
                JOIN patients p ON a.patient_id = p.id
                WHERE q.status = 'in_progress' 
                AND DATE(q.created_at) = CURDATE() 
                ORDER BY q.id DESC LIMIT 1";
        $result = $this->query($sql);
        return $result[0] ?? null;
    }

    public function getWaitingCount() {
        $sql = "SELECT COUNT(*) as count FROM queue 
                WHERE status = 'waiting' AND DATE(created_at) = CURDATE()";
        $result = $this->query($sql);
        return $result[0]['count'] ?? 0;
    }

    public function updateQueueStatus($appointmentId, $status) {
        $sql = "UPDATE queue SET status = :status, end_time = NOW() 
                WHERE appointment_id = :appointment_id 
                AND DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['status' => $status, 'appointment_id' => $appointmentId]);
    }

    public function callNext($queueId) {
        // Mark current as completed
        $sql1 = "UPDATE queue SET status = 'completed', end_time = NOW() 
                 WHERE status = 'in_progress' AND DATE(created_at) = CURDATE()";
        $this->db->exec($sql1);

        // Set next as in_progress
        $sql2 = "UPDATE queue SET status = 'in_progress', start_time = NOW() 
                 WHERE id = :id";
        $stmt = $this->db->prepare($sql2);
        $stmt->execute(['id' => $queueId]);
    }
}