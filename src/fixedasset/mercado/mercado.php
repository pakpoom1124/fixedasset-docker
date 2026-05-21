<?php

session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';

// [FIX] Docker/PHP 8 migration:
// ห้าม include header2.php ตรงนี้
// เพราะ header2.php มี HTML output
// ถ้า include ก่อน header("Location: mercado.php")
// จะเกิด error: Cannot modify header information - headers already sent

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
$user = $_SESSION['user'];
$can_edit = $user['can_edit'] ?? 0;
$can_delete = $user['can_delete'] ?? 0;

$upload_dir = '../img/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $id = $_POST['id'] ?? 0;
    
    $fields = ['code_id','name','serialno','model','compname','startdate','expdate','status','remark','location_id','type_id','position_id']; 
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = $_POST[$f] ?? '';
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $image_db_path = $_POST['current_image_path'] ?? '';     
    $image_path_to_save_to_db = '';
    $image_path_to_delete = '';

    if (!empty($image_db_path)) {
        $image_filename = basename($image_db_path);
        $image_path_to_delete = $upload_dir . $image_filename;
        $image_path_to_save_to_db = $image_path_to_delete;
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('mercado_', true) . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            if (!empty($image_path_to_delete) && file_exists($image_path_to_delete)) {
                @unlink($image_path_to_delete); 
            }
            $image_path_to_save_to_db = $target_file;
        }
    }
    
    if ($id && isset($_POST['delete_image']) && $_POST['delete_image'] === '1') {
        if (!empty($image_path_to_delete) && file_exists($image_path_to_delete)) {
            @unlink($image_path_to_delete);
        }
        $image_path_to_save_to_db = '';
    }

    $data['image'] = $image_path_to_save_to_db; 
    $fields[] = 'image'; 

    $bind_types = "sssssssssiiis"; 
    $bind_params = [&$data['code_id'], &$data['name'], &$data['serialno'], &$data['model'], &$data['compname'], &$data['startdate'], &$data['expdate'], &$data['status'], &$data['remark'], &$data['location_id'], &$data['type_id'], &$data['position_id'], &$data['image']];
    
    if ($id) {
        $sql = "UPDATE mercado SET code_id=?, name=?, serialno=?, model=?, compname=?, startdate=?, expdate=?, status=?, remark=?, location_id=?, type_id=?, position_id=?, image=? WHERE id=?";
        $bind_types .= "i";
        $bind_params[] = &$id;

    } else {
        $sql = "INSERT INTO mercado (code_id, name, serialno, model, compname, startdate, expdate, status, remark, location_id, type_id, position_id, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }
    
    call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_types], $bind_params));
    
    $stmt->execute();
    
    if ($stmt->error) {
        die("MySQL Execute Error: " . $stmt->error);
    }
    
    header("Location: mercado.php");
    exit;
}

if (isset($_GET['delete']) && $can_delete) {
    $id = (int)$_GET['delete'];
    
    $stmt_select = $conn->prepare("SELECT image FROM mercado WHERE id=?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $old_image_db_path = $result_select->fetch_assoc()['image'] ?? '';
    
    if (!empty($old_image_db_path)) {
        $image_filename = basename($old_image_db_path);
        $image_path_to_delete = $upload_dir . $image_filename; 

        if (file_exists($image_path_to_delete)) {
            @unlink($image_path_to_delete);
        }
    }
    
    $stmt_delete = $conn->prepare("DELETE FROM mercado WHERE id=?");
    if ($stmt_delete === false) {
        die("MySQL Delete Prepare Error: " . $conn->error);
    }
    $stmt_delete->bind_param("i", $id);
    $stmt_delete->execute();

    if ($stmt_delete->error) {
        die("MySQL Delete Execute Error: " . $stmt_delete->error);
    }
    
    header("Location: mercado.php");
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM mercado WHERE id=$id")->fetch_assoc(); 
}
$locations = $conn->query("SELECT * FROM locations");
$positions = $conn->query("SELECT * FROM positions");
$types = $conn->query("
    SELECT at.id, at.name FROM asset_types at 
    LEFT JOIN category c ON at.category_id = c.id 
    WHERE c.cat_name = 'Computer'
    ORDER BY at.name ASC
");

$search = $_GET['search'] ?? '';
$filter_location = $_GET['filter_location'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_position = $_GET['filter_position'] ?? '';


$where = [];
$params = [];
$paramTypes = "";

if ($filter_location !== '') {
    $where[] = "a.location_id = ?";
    $params[] = $filter_location;
    $paramTypes .= "i";
}
if ($filter_type !== '') {
    $where[] = "a.type_id = ?";
    $params[] = $filter_type;
    $paramTypes .= "i";
}
if ($filter_position !== '') {
    $where[] = "a.position_id = ?";
    $params[] = $filter_position;
    $paramTypes .= "i";
}

if ($search !== '') {
    $searchClause = "(a.code_id LIKE ? OR a.name LIKE ? OR a.serialno LIKE ? OR a.model LIKE ? OR a.compname LIKE ? OR a.status LIKE ? OR a.remark LIKE ? OR a.image LIKE ? OR t.name LIKE ?)";
    $where[] = $searchClause;
    for ($i = 0; $i < 9; $i++) {
        $params[] = "%$search%";
        $paramTypes .= "s";
    }
}


$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as c FROM mercado a LEFT JOIN asset_types t ON a.type_id = t.id $whereSql";

$stmt = $conn->prepare($count_sql);
if ($params) $stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total / $limit);

$data_sql = "
    SELECT a.*, l.name AS location_name, t.name AS type_name, p.name AS position_name
    FROM mercado a
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN asset_types t ON a.type_id = t.id
    LEFT JOIN positions p ON a.position_id = p.id
    $whereSql
    ORDER BY a.id ASC
    LIMIT $limit OFFSET $offset
"; 
$stmt = $conn->prepare($data_sql);
if ($params) $stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$assets = $stmt->get_result();

// Dropdown Data

// [FIX] MySQL 8 / Docker migration:
// MySQL 8 เปิด ONLY_FULL_GROUP_BY เป็น default
// ดังนั้นห้ามใช้ SELECT * ร่วมกับ GROUP BY name
// เพราะ column อื่น เช่น id ไม่ได้อยู่ใน GROUP BY และไม่ได้ aggregate
// วิธีแก้คือเลือก id แบบ deterministic ด้วย MIN(id)
$asset_types = $conn->query("
    SELECT MIN(id) AS id, name
    FROM asset_types
    GROUP BY name
    ORDER BY name ASC
");
// [FIX] include header2.php หลังจาก insert/update/delete redirect logic เสร็จแล้วเท่านั้น
// เพื่อให้ header("Location: mercado.php") ทำงานได้ก่อนมี HTML output
include '../template/header2.php';
?>

<div class="container mt-4">
    <h3>El Mercado Computer</h3>

    <?php if ($can_edit): ?>
    <form method="post" class="row g-2 mb-3" enctype="multipart/form-data"> 
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <input type="hidden" name="current_image_path" value="<?= htmlspecialchars($edit['image'] ?? '') ?>">
		
<?php
$fields = [
    'code_id' => 'Code id', 'name' => 'Name', 'serialno' => 'Serial No', 'model' => 'Model',
    'compname' => 'Comp name', 'startdate' => 'Start date', 'expdate' => 'Expire date',
    'status' => 'Status', 'remark' => 'Note'
];
foreach ($fields as $f => $label): ?>
    <div class="col-md-3">
        <label><?= $label ?></label>
        <?php if ($f === 'status'): ?>
            <select name="status" class="form-select">
                <?php
                $statusOptions = ['', 'ใช้งาน', 'เสีย', 'รอซ่อม', 'รอ WriteOff', 'Spare'];
                foreach ($statusOptions as $option):
                ?>
                    <option value="<?= $option ?>" <?= ($edit['status'] ?? '') === $option ? 'selected' : '' ?>>
                        <?= $option ?>
                    </option>
                <?php endforeach ?>
            </select>
        <?php else: ?>
            <input type="<?= in_array($f, ['startdate', 'expdate']) ? 'date' : 'text' ?>"
                   name="<?= $f ?>"
                   class="form-control"
                   value="<?= htmlspecialchars($edit[$f] ?? '') ?>">
        <?php endif ?>
    </div>
<?php endforeach; ?>		
		
    <div class="col-md-3">
        <label>Image File</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        <?php 
        $display_image_path = '';
        if (!empty($edit['image'])) {
            $image_filename = basename($edit['image']);
            $display_image_path = $upload_dir . $image_filename;
        }
        if (file_exists($display_image_path) && !empty($display_image_path)): // ตรวจสอบว่าไฟล์มีอยู่จริง
        ?>
            <div class="mt-1">
                <a href="javascript:void(0)" onclick="showImage('<?= htmlspecialchars($display_image_path) ?>')">
                    <img src="<?= htmlspecialchars($display_image_path) ?>" 
                         style="width: 100px; height: 80px; object-fit: cover; cursor:pointer;" 
                         alt="Current Image">
                </a>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-3">
            <label>Department</label>
            <select name="position_id" class="form-select">
                <option value="">-- Choose --</option>
                <?php mysqli_data_seek($positions, 0); while($p = $positions->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= ($edit['position_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= $p['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label>Location</label>
            <select name="location_id" class="form-select" required>
                <option value="">-- Choose --</option>
                <?php mysqli_data_seek($locations, 0); while($l = $locations->fetch_assoc()): ?>
                    <option value="<?= $l['id'] ?>" <?= ($edit['location_id'] ?? '') == $l['id'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label>Type</label>
		<select name="type_id" class="form-select" required>
        <option value="">-- Choose --</option>
        <?php 
        if ($asset_types->num_rows > 0):
            mysqli_data_seek($asset_types, 0); 
            while($t = $asset_types->fetch_assoc()): 
        ?>
            <option value="<?= $t['id'] ?>" <?= (isset($edit['type_id']) && $edit['type_id'] == $t['id']) ? 'selected' : '' ?>> <?= htmlspecialchars($t['name']) ?>
            </option>
        <?php endwhile; endif; ?>
    	</select>
        </div>
		
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-success w-100">บันทึก</button>
        </div>
    </form>
    <?php endif; ?>	
	<div class="mb-2"><a href="mercado_export.php" class="btn btn-outline-success">Export Excel</a></div>

    <form class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="ค้นหา..."></div>
        <div class="col-md-3">
            <select name="filter_location" class="form-select">
                <option value="">-- สถานที่ --</option>
                <?php mysqli_data_seek($locations, 0); while($l = $locations->fetch_assoc()): ?>
                    <option value="<?= $l['id'] ?>" <?= $filter_location == $l['id'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_type" class="form-select">
                <option value="">-- ประเภท --</option>
                <?php mysqli_data_seek($types, 0); while($t = $types->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>" <?= $filter_type == $t['id'] ? 'selected' : '' ?>><?= $t['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_position" class="form-select">
                <option value="">-- แผนก --</option>
                <?php mysqli_data_seek($positions, 0); while($p = $positions->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= $filter_position == $p['id'] ? 'selected' : '' ?>><?= $p['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2 mt-2"><button class="btn btn-primary w-100">ค้นหา</button></div>
        <div class="col-md-2 mt-2"><a href="mercado.php" class="btn btn-secondary w-100">ล้าง</a></div>
    </form>

	
	<nav><ul class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor ?>
    </ul></nav>	
	
	
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th><th>Code</th><th>Name</th><th>Department</th><th>Location</th><th>Serial no</th><th>Model</th><th>Type</th><th>Comp name</th><th>Start date</th><th>Expire date</th><th>Status</th><th>Note</th><th>Image</th><th width="130">Action</th> 
            </tr>
        </thead>
        <tbody>
            <?php $i = ($page - 1) * $limit + 1; ?>
            <?php while($row = $assets->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['code_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <!-- [FIX] PHP 8 / Docker migration:
                บางรายการไม่มี position_id ที่ match กับตาราง positions
                ทำให้ position_name เป็น NULL และ htmlspecialchars(NULL) จะขึ้น Deprecated warning -->
                <td><?= htmlspecialchars($row['position_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['location_name']) ?></td>
                <td><?= htmlspecialchars($row['serialno']) ?></td>
                <td><?= htmlspecialchars($row['model']) ?></td>
                <td><?= htmlspecialchars($row['type_name']) ?></td>
                <td><?= htmlspecialchars($row['compname']) ?></td>
                <td><?= ($row['startdate'] && $row['startdate'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['startdate'])) : '' ?></td>
				<td><?= ($row['expdate'] && $row['expdate'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['expdate'])) : '' ?></td>
                <td><?= $row['status'] ?></td>
                <td><?= $row['remark'] ?></td>
                <td>
                    <?php if (!empty($row['image'])): ?>
                        <?php 
                            // NEW: Normalize path สำหรับแสดงผล
                            $image_filename = basename($row['image']);
                            $display_image_path = $upload_dir . $image_filename;
                        ?>
                        <a href="javascript:void(0)" onclick="showImage('<?= htmlspecialchars($display_image_path) ?>')">
                            <img src="<?= htmlspecialchars($display_image_path) ?>" 
                                 style="width: 100px; height: 80px; object-fit: cover; display: block; cursor:pointer;"
                                 alt="Asset Image">
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($can_edit): ?>
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                    <?php endif ?>
                    <?php if ($can_delete): ?>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบรายการนี้?')">🗑️</a>
                    <?php endif ?>
                    <a href="print_qr.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">QR</a>
                </td>
            </tr>
            <?php endwhile ?>
        </tbody>
    </table>

    <nav><ul class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor ?>
    </ul></nav>
</div>

<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:820px;">
        <div class="modal-content">
            <div class="modal-body d-flex justify-content-center align-items-center p-0" style="max-height: 620px;"> 
                <img id="modalImage" src="" style="max-width:1024px; max-height:768px; width:auto; height:auto; object-fit:contain; cursor:pointer;">
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script> 
<script>
function showImage(src) {
    document.getElementById('modalImage').src = src;
    var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
    myModal.show();
}

document.getElementById('modalImage').addEventListener('click', function() {
    var myModal = bootstrap.Modal.getInstance(document.getElementById('imageModal'));
    if (myModal) {
        myModal.hide();
    }
});
</script>

<?php include '../template/footer.php'; ?>