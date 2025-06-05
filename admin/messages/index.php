<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Set flash message
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'You do not have permission to access the admin area.'
    ];
    
    // Redirect to login page
    header('Location: ../../login.php');
    exit;
}

// Include database connection
require_once dirname(dirname(__DIR__)) . '/connection.php';

/**
 * Admin Messages Page
 * 
 * This page displays all contact form messages and allows admin to manage them.
 */

// Page title and current section (used by the header)
$page_title = "Contact Messages";
$current_section = "messages";

// Include admin header
require_once '../includes/admin-header.php';

// Define status colors
$status_colors = [
    'new' => 'danger',
    'read' => 'warning',
    'replied' => 'success',
    'archived' => 'secondary'
];

// Handle status updates
if (isset($_POST['action']) && isset($_POST['message_id'])) {
    $message_id = (int)$_POST['message_id'];
    $action = $_POST['action'];
    
    if ($action === 'read' || $action === 'replied' || $action === 'archived') {
        $sql = "UPDATE contact_messages SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $action, $message_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM contact_messages WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get filter params
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare the query
$sql = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

// Add order by
$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get counts for each status
$counts = [
    'all' => 0,
    'new' => 0,
    'read' => 0,
    'replied' => 0,
    'archived' => 0
];

$count_sql = "SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status";
$count_result = $conn->query($count_sql);
if ($count_result) {
    while ($row = $count_result->fetch_assoc()) {
        $counts[$row['status']] = $row['count'];
    }
}
$counts['all'] = array_sum($counts);
?>

<div class="admin-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Contact Messages</h5>
                        <div class="header-actions">
                            <a href="#" class="btn btn-sm btn-primary refresh-button">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Filter and Search -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="btn-group status-filter" role="group">
                                    <a href="?status=" class="btn btn-outline-secondary <?php echo $status_filter === '' ? 'active' : ''; ?>">
                                        All <span class="badge bg-secondary"><?php echo $counts['all']; ?></span>
                                    </a>
                                    <a href="?status=new" class="btn btn-outline-danger <?php echo $status_filter === 'new' ? 'active' : ''; ?>">
                                        New <span class="badge bg-danger"><?php echo $counts['new']; ?></span>
                                    </a>
                                    <a href="?status=read" class="btn btn-outline-warning <?php echo $status_filter === 'read' ? 'active' : ''; ?>">
                                        Read <span class="badge bg-warning"><?php echo $counts['read']; ?></span>
                                    </a>
                                    <a href="?status=replied" class="btn btn-outline-success <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">
                                        Replied <span class="badge bg-success"><?php echo $counts['replied']; ?></span>
                                    </a>
                                    <a href="?status=archived" class="btn btn-outline-secondary <?php echo $status_filter === 'archived' ? 'active' : ''; ?>">
                                        Archived <span class="badge bg-secondary"><?php echo $counts['archived']; ?></span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form method="get" class="search-form">
                                    <?php if (!empty($status_filter)): ?>
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                    <?php endif; ?>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Search messages..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <?php if (empty($messages)): ?>
                            <div class="alert alert-info">
                                No messages found. <?php echo !empty($search) ? 'Try adjusting your search criteria.' : ''; ?>
                            </div>
                        <?php else: ?>
                            <!-- Messages List -->
                            <div class="table-responsive">
                                <table class="table table-hover message-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                            <tr data-message-id="<?php echo $message['id']; ?>" class="<?php echo $message['status'] === 'new' ? 'table-new' : ''; ?>">
                                                <td><?php echo $message['id']; ?></td>
                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                <td>
                                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($message['email']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="#" class="view-message" data-bs-toggle="modal" data-bs-target="#messageModal" 
                                                       data-message-id="<?php echo $message['id']; ?>"
                                                       data-message-name="<?php echo htmlspecialchars($message['name']); ?>"
                                                       data-message-email="<?php echo htmlspecialchars($message['email']); ?>"
                                                       data-message-phone="<?php echo htmlspecialchars($message['phone']); ?>"
                                                       data-message-subject="<?php echo htmlspecialchars($message['subject']); ?>"
                                                       data-message-content="<?php echo htmlspecialchars($message['message']); ?>"
                                                       data-message-date="<?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?>"
                                                       data-message-status="<?php echo $message['status']; ?>">
                                                        <?php echo htmlspecialchars($message['subject']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_colors[$message['status']]; ?>">
                                                        <?php echo ucfirst($message['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary view-message" 
                                                                data-bs-toggle="modal" data-bs-target="#messageModal" 
                                                                data-message-id="<?php echo $message['id']; ?>"
                                                                data-message-name="<?php echo htmlspecialchars($message['name']); ?>"
                                                                data-message-email="<?php echo htmlspecialchars($message['email']); ?>"
                                                                data-message-phone="<?php echo htmlspecialchars($message['phone']); ?>"
                                                                data-message-subject="<?php echo htmlspecialchars($message['subject']); ?>"
                                                                data-message-content="<?php echo htmlspecialchars($message['message']); ?>"
                                                                data-message-date="<?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?>"
                                                                data-message-status="<?php echo $message['status']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-sm btn-outline-success reply-message"
                                                                data-email="<?php echo htmlspecialchars($message['email']); ?>"
                                                                data-subject="Re: <?php echo htmlspecialchars($message['subject']); ?>">
                                                            <i class="fas fa-reply"></i>
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-message" 
                                                                data-message-id="<?php echo $message['id']; ?>"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Detail Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="message-header mb-3">
                    <div class="d-flex justify-content-between">
                        <h5 id="message-subject" class="mb-1"></h5>
                        <span id="message-status-badge" class="badge"></span>
                    </div>
                    <div class="text-muted small">
                        From: <span id="message-from"></span> &lt;<span id="message-email"></span>&gt;
                        <span id="message-phone-container">• Phone: <span id="message-phone"></span></span>
                    </div>
                    <div class="text-muted small">
                        Received on <span id="message-date"></span>
                    </div>
                </div>
                
                <div class="message-body p-3 bg-light rounded mb-3">
                    <p id="message-content" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <form id="status-form" method="post" class="d-inline">
                            <input type="hidden" name="message_id" id="modal-message-id">
                            <input type="hidden" name="action" id="message-action">
                            
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary status-button" data-action="read">
                                    <i class="fas fa-check"></i> Mark as Read
                                </button>
                                <button type="button" class="btn btn-outline-success status-button" data-action="replied">
                                    <i class="fas fa-check-double"></i> Mark as Replied
                                </button>
                                <button type="button" class="btn btn-outline-secondary status-button" data-action="archived">
                                    <i class="fas fa-archive"></i> Archive
                                </button>
                            </div>
                        </form>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary reply-from-modal">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this message? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="post" id="delete-form">
                    <input type="hidden" name="message_id" id="delete-message-id">
                    <input type="hidden" name="action" value="delete">
                    
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.message-table tbody tr.table-new {
    font-weight: bold;
    background-color: rgba(0, 123, 255, 0.05);
}
.status-filter .active {
    font-weight: bold;
}
.message-body {
    white-space: pre-line;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View message in modal
    const messageModal = document.getElementById('messageModal');
    if (messageModal) {
        messageModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const messageId = button.getAttribute('data-message-id');
            const messageName = button.getAttribute('data-message-name');
            const messageEmail = button.getAttribute('data-message-email');
            const messagePhone = button.getAttribute('data-message-phone');
            const messageSubject = button.getAttribute('data-message-subject');
            const messageContent = button.getAttribute('data-message-content');
            const messageDate = button.getAttribute('data-message-date');
            const messageStatus = button.getAttribute('data-message-status');
            
            // Update modal content
            document.getElementById('message-subject').textContent = messageSubject;
            document.getElementById('message-from').textContent = messageName;
            document.getElementById('message-email').textContent = messageEmail;
            document.getElementById('message-content').textContent = messageContent;
            document.getElementById('message-date').textContent = messageDate;
            document.getElementById('modal-message-id').value = messageId;
            
            // Phone display
            const phoneContainer = document.getElementById('message-phone-container');
            const phoneElement = document.getElementById('message-phone');
            if (messagePhone) {
                phoneElement.textContent = messagePhone;
                phoneContainer.style.display = 'inline';
            } else {
                phoneContainer.style.display = 'none';
            }
            
            // Status badge
            const statusBadge = document.getElementById('message-status-badge');
            let badgeClass = 'bg-secondary';
            
            switch (messageStatus) {
                case 'new':
                    badgeClass = 'bg-danger';
                    break;
                case 'read':
                    badgeClass = 'bg-warning';
                    break;
                case 'replied':
                    badgeClass = 'bg-success';
                    break;
                case 'archived':
                    badgeClass = 'bg-secondary';
                    break;
            }
            
            statusBadge.className = 'badge ' + badgeClass;
            statusBadge.textContent = messageStatus.charAt(0).toUpperCase() + messageStatus.slice(1);
            
            // If status is 'new', mark as read automatically
            if (messageStatus === 'new') {
                document.getElementById('message-action').value = 'read';
                document.getElementById('status-form').submit();
            }
        });
    }
    
    // Handle status buttons
    const statusButtons = document.querySelectorAll('.status-button');
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            document.getElementById('message-action').value = action;
            document.getElementById('status-form').submit();
        });
    });
    
    // Handle delete confirmation
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const messageId = button.getAttribute('data-message-id');
            document.getElementById('delete-message-id').value = messageId;
        });
    }
    
    // Handle reply button
    const replyButtons = document.querySelectorAll('.reply-message');
    replyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const email = this.getAttribute('data-email');
            const subject = this.getAttribute('data-subject');
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}`;
        });
    });
    
    // Handle reply from modal
    const replyFromModalButton = document.querySelector('.reply-from-modal');
    if (replyFromModalButton) {
        replyFromModalButton.addEventListener('click', function() {
            const email = document.getElementById('message-email').textContent;
            const subject = 'Re: ' + document.getElementById('message-subject').textContent;
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}`;
            
            // Mark as replied
            document.getElementById('message-action').value = 'replied';
            document.getElementById('status-form').submit();
        });
    }
    
    // Handle refresh button
    const refreshButton = document.querySelector('.refresh-button');
    if (refreshButton) {
        refreshButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.reload();
        });
    }
});
</script>

<?php
// Include admin footer
require_once '../includes/admin-footer.php';
?> 