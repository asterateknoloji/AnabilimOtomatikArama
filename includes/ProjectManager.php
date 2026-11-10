<?php
/**
 * Proje Yönetim Sınıfı
 */

class ProjectManager
{
    private $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Tüm projeleri getir
     */
    public function getProjects($activeOnly = true)
    {
        $sql = "SELECT * FROM PROJECTS WHERE 1=1";
        
        if ($activeOnly) {
            $sql .= " AND Active = 1";
        }
        
        $sql .= " ORDER BY CreatedDate DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Tüm projeleri getir (alias)
     */
    public function getAllProjects($activeOnly = true)
    {
        return $this->getProjects($activeOnly);
    }
    
    /**
     * Proje getir
     */
    public function getProject($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM PROJECTS WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Proje oluştur
     */
    public function createProject($name, $description = '', $templateId = null, $callerID = null, $trunk = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO PROJECTS (ProjectName, Description, TemplateId, CallerID, Trunk, CreatedDate, UpdatedDate)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$name, $description, $templateId, $callerID, $trunk]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Proje güncelle
     */
    public function updateProject($id, $name, $description, $templateId = null, $callerID = null, $trunk = null)
    {
        $stmt = $this->pdo->prepare("
            UPDATE PROJECTS 
            SET ProjectName = ?, Description = ?, TemplateId = ?, CallerID = ?, Trunk = ?, UpdatedDate = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$name, $description, $templateId, $callerID, $trunk, $id]);
    }
    
    /**
     * Proje sil
     */
    public function deleteProject($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM PROJECTS WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Proje istatistikleri
     */
    public function getProjectStats($projectId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as calling,
                SUM(CASE WHEN Status = 3 THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN Status = 4 THEN 1 ELSE 0 END) as failed
            FROM CALLS
            WHERE ProjectId = ?
        ");
        
        $stmt->execute([$projectId]);
        return $stmt->fetch();
    }
    
    /**
     * Proje import'larını getir
     */
    public function getProjectImports($projectId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM IMPORT_HISTORY
            WHERE ProjectId = ?
            ORDER BY ImportDate DESC
        ");
        
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
}
