<?php
class Notification extends Model {
    protected $table = 'notifications';
    
    public function getUnread($userId) {
        $sql = "SELECT id, title, message, type, link, 
                DATE_FORMAT(created_at, '%b %d, %Y %h:%i %p') as time 
                FROM notifications 
                WHERE user_id = :user_id AND is_read = 0 
                ORDER BY created_at DESC LIMIT 10";
        return $this->query($sql, ['user_id' => $userId]);
    }
    
    public function markAsRead($id) {
        return $this->update($id, ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }
    
    public function createNotification($data) {
        return $this->create($data);
    }
    
    public function notifyDoctor($doctorId, $title, $message) {
        $sql = "SELECT user_id FROM doctors WHERE id = :id";
        $result = $this->query($sql, ['id' => $doctorId]);
        if($result) {
            $this->createNotification([
                'user_id' => $result[0]['user_id'],
                'title' => $title,
                'message' => $message,
                'type' => 'info'
            ]);
        }
    }
}