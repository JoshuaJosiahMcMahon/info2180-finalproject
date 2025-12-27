<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add':
        addNote();
        break;
    case 'list':
        listNotes();
        break;
    default:
        echo '<div class="error-message">Invalid action.</div>';
}

function addNote() {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<div class="error-message">Invalid request method.</div>';
        return;
    }
    
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    $comment = isset($_POST['comment']) ? sanitize($_POST['comment']) : '';

    if ($contact_id <= 0) {
        echo '<div class="error-message">Invalid contact ID.</div>';
        return;
    }
    
    if (empty($comment)) {
        echo '<div class="error-message">Please enter a note.</div>';
        return;
    }
    
    try {
        $pdo = getDB();

        $stmt = $pdo->prepare("SELECT id FROM contacts WHERE id = ?");
        $stmt->execute([$contact_id]);
        if (!$stmt->fetch()) {
            echo '<div class="error-message">Contact not found.</div>';
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO notes (contact_id, comment, created_by, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$contact_id, $comment, $_SESSION['user_id']]);

        $stmt = $pdo->prepare("UPDATE contacts SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$contact_id]);
        
        echo '<div class="success-message">Note added successfully!</div>';
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred. Please try again.</div>';
    }
}

function listNotes() {
    requireLogin();
    
    $contact_id = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : 0;
    
    if ($contact_id <= 0) {
        echo '<div class="error-message">Invalid contact ID.</div>';
        return;
    }
    
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            SELECT n.*, u.firstname, u.lastname 
            FROM notes n
            JOIN users u ON n.created_by = u.id
            WHERE n.contact_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$contact_id]);
        $notes = $stmt->fetchAll();
        
        if (empty($notes)): ?>
        <p class="no-notes">No notes yet.</p>
        <?php else: ?>
        <?php foreach ($notes as $note): ?>
        <div class="note-item">
            <div class="note-header">
                <strong><?php echo sanitize($note['firstname'] . ' ' . $note['lastname']); ?></strong>
                <span class="note-date"><?php echo date('M d, Y H:i', strtotime($note['created_at'])); ?></span>
            </div>
            <div class="note-content">
                <?php echo nl2br(sanitize($note['comment'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif;
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred loading notes.</div>';
    }
}
?>

