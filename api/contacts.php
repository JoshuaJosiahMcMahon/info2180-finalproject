<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'list':
        listContacts();
        break;
    case 'form':
        showAddContactForm();
        break;
    case 'add':
        addContact();
        break;
    case 'view':
        viewContact();
        break;
    case 'assign':
        assignToMe();
        break;
    case 'toggle_type':
        toggleType();
        break;
    default:
        echo '<div class="error-message">Invalid action.</div>';
}

function listContacts() {
    requireLogin();
    
    $filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
    
    try {
        $pdo = getDB();
        
        $sql = "SELECT c.id, c.title, c.firstname, c.lastname, c.email, c.company, c.type 
                FROM contacts c";
        $params = [];
        
        switch ($filter) {
            case 'sales':
                $sql .= " WHERE c.type = 'Sales Lead'";
                break;
            case 'support':
                $sql .= " WHERE c.type = 'Support'";
                break;
            case 'assigned':
                $sql .= " WHERE c.assigned_to = ?";
                $params[] = $_SESSION['user_id'];
                break;
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();
        ?>
        <div class="page-header">
            <h2>Dashboard - Contacts</h2>
            <a href="#" class="btn btn-primary" data-page="new-contact">Add New Contact</a>
        </div>
        
        <div class="filters">
            <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all">All Contacts</button>
            <button class="filter-btn <?php echo $filter === 'sales' ? 'active' : ''; ?>" data-filter="sales">Sales Leads</button>
            <button class="filter-btn <?php echo $filter === 'support' ? 'active' : ''; ?>" data-filter="support">Support</button>
            <button class="filter-btn <?php echo $filter === 'assigned' ? 'active' : ''; ?>" data-filter="assigned">Assigned to Me</button>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="5" class="text-center">No contacts found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td>
                        <a href="#" class="contact-link" data-contact-id="<?php echo $contact['id']; ?>">
                            <?php echo sanitize($contact['title'] . ' ' . $contact['firstname'] . ' ' . $contact['lastname']); ?>
                        </a>
                    </td>
                    <td><?php echo sanitize($contact['email']); ?></td>
                    <td><?php echo sanitize($contact['company']); ?></td>
                    <td><span class="badge badge-<?php echo $contact['type'] === 'Sales Lead' ? 'sales' : 'support'; ?>"><?php echo sanitize($contact['type']); ?></span></td>
                    <td>
                        <a href="#" class="btn btn-small view-contact-btn" data-contact-id="<?php echo $contact['id']; ?>">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred loading contacts.</div>';
    }
}

function showAddContactForm() {
    requireLogin();
    
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT id, firstname, lastname FROM users ORDER BY firstname, lastname");
        $users = $stmt->fetchAll();
        ?>
        <div class="page-header">
            <h2>Add New Contact</h2>
        </div>
        <div class="form-container">
            <form id="add-contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <select id="title" name="title" required>
                            <option value="">Select Title</option>
                            <option value="Mr">Mr</option>
                            <option value="Mrs">Mrs</option>
                            <option value="Ms">Ms</option>
                            <option value="Dr">Dr</option>
                            <option value="Prof">Prof</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="firstname">First Name *</label>
                        <input type="text" id="firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name *</label>
                        <input type="text" id="lastname" name="lastname" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Telephone</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>
                </div>
                <div class="form-group">
                    <label for="company">Company</label>
                    <input type="text" id="company" name="company">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="Sales Lead">Sales Lead</option>
                            <option value="Support">Support</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assigned_to">Assigned To</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">Not Assigned</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo sanitize($user['firstname'] . ' ' . $user['lastname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="contact-form-message"></div>
                <button type="submit" class="btn btn-primary">Create Contact</button>
            </form>
        </div>
        <?php
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred loading the form.</div>';
    }
}

function addContact() {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<div class="error-message">Invalid request method.</div>';
        return;
    }
    
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $firstname = isset($_POST['firstname']) ? sanitize($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? sanitize($_POST['lastname']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? sanitize($_POST['telephone']) : '';
    $company = isset($_POST['company']) ? sanitize($_POST['company']) : '';
    $type = isset($_POST['type']) ? sanitize($_POST['type']) : '';
    $assigned_to = isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;

    if (empty($title) || empty($firstname) || empty($lastname) || empty($email) || empty($type)) {
        echo '<div class="error-message">Please fill in all required fields.</div>';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="error-message">Please enter a valid email address.</div>';
        return;
    }

    if (!in_array($title, ['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'])) {
        echo '<div class="error-message">Invalid title selected.</div>';
        return;
    }

    if (!in_array($type, ['Sales Lead', 'Support'])) {
        echo '<div class="error-message">Invalid type selected.</div>';
        return;
    }
    
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("INSERT INTO contacts (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$title, $firstname, $lastname, $email, $telephone, $company, $type, $assigned_to, $_SESSION['user_id']]);
        
        echo '<div class="success-message">Contact created successfully!</div>';
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred. Please try again.</div>';
    }
}

function viewContact() {
    requireLogin();
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo '<div class="error-message">Invalid contact ID.</div>';
        return;
    }
    
    try {
        $pdo = getDB();

        $stmt = $pdo->prepare("
            SELECT c.*, 
                   creator.firstname as creator_firstname, creator.lastname as creator_lastname,
                   assignee.firstname as assignee_firstname, assignee.lastname as assignee_lastname
            FROM contacts c
            LEFT JOIN users creator ON c.created_by = creator.id
            LEFT JOIN users assignee ON c.assigned_to = assignee.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $contact = $stmt->fetch();
        
        if (!$contact) {
            echo '<div class="error-message">Contact not found.</div>';
            return;
        }

        $stmt = $pdo->prepare("
            SELECT n.*, u.firstname, u.lastname 
            FROM notes n
            JOIN users u ON n.created_by = u.id
            WHERE n.contact_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$id]);
        $notes = $stmt->fetchAll();
        ?>
        <div class="page-header">
            <h2><?php echo sanitize($contact['title'] . ' ' . $contact['firstname'] . ' ' . $contact['lastname']); ?></h2>
            <a href="#" class="btn btn-secondary" data-page="dashboard">Back to Dashboard</a>
        </div>
        
        <div class="contact-details">
            <div class="detail-card">
                <h3>Contact Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Email:</label>
                        <span><?php echo sanitize($contact['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Telephone:</label>
                        <span><?php echo sanitize($contact['telephone']) ?: 'N/A'; ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Company:</label>
                        <span><?php echo sanitize($contact['company']) ?: 'N/A'; ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Type:</label>
                        <span class="badge badge-<?php echo $contact['type'] === 'Sales Lead' ? 'sales' : 'support'; ?>"><?php echo sanitize($contact['type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Assigned To:</label>
                        <span><?php echo $contact['assignee_firstname'] ? sanitize($contact['assignee_firstname'] . ' ' . $contact['assignee_lastname']) : 'Not Assigned'; ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Created By:</label>
                        <span><?php echo sanitize($contact['creator_firstname'] . ' ' . $contact['creator_lastname']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Created At:</label>
                        <span><?php echo date('M d, Y H:i', strtotime($contact['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Last Updated:</label>
                        <span><?php echo date('M d, Y H:i', strtotime($contact['updated_at'])); ?></span>
                    </div>
                </div>
                
                <div class="contact-actions">
                    <button class="btn btn-primary assign-to-me-btn" data-contact-id="<?php echo $contact['id']; ?>">Assign to Me</button>
                    <button class="btn btn-secondary toggle-type-btn" data-contact-id="<?php echo $contact['id']; ?>" data-current-type="<?php echo $contact['type']; ?>">
                        Switch to <?php echo $contact['type'] === 'Sales Lead' ? 'Support' : 'Sales Lead'; ?>
                    </button>
                </div>
                <div id="contact-action-message"></div>
            </div>
        </div>
        
        <div class="notes-section">
            <h3>Notes</h3>
            
            <div class="add-note-form">
                <form id="add-note-form" data-contact-id="<?php echo $contact['id']; ?>">
                    <div class="form-group">
                        <label for="note-comment">Add a Note</label>
                        <textarea id="note-comment" name="comment" rows="3" required placeholder="Enter your note here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </form>
                <div id="note-form-message"></div>
            </div>
            
            <div id="notes-list" class="notes-list">
                <?php if (empty($notes)): ?>
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
                <?php endif; ?>
            </div>
        </div>
        <?php
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred loading contact details.</div>';
    }
}

function assignToMe() {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<div class="error-message">Invalid request method.</div>';
        return;
    }
    
    $id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    
    if ($id <= 0) {
        echo '<div class="error-message">Invalid contact ID.</div>';
        return;
    }
    
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("UPDATE contacts SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
        
        echo '<div class="success-message">Contact assigned to you successfully!</div>';
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred. Please try again.</div>';
    }
}

function toggleType() {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<div class="error-message">Invalid request method.</div>';
        return;
    }
    
    $id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    $current_type = isset($_POST['current_type']) ? sanitize($_POST['current_type']) : '';
    
    if ($id <= 0) {
        echo '<div class="error-message">Invalid contact ID.</div>';
        return;
    }
    
    $new_type = $current_type === 'Sales Lead' ? 'Support' : 'Sales Lead';
    
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("UPDATE contacts SET type = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_type, $id]);
        
        echo '<div class="success-message">Contact type changed to ' . $new_type . ' successfully!</div>';
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred. Please try again.</div>';
    }
}
?>

