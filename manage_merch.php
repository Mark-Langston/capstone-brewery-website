<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function setFlash(string $message, string $type = 'success'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash_message'])) return null;
    $flash = ['message' => $_SESSION['flash_message'], 'type' => $_SESSION['flash_type']];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return $flash;
}

function writeAuditLog(PDO $pdo, int $userId, string $entityType, ?int $entityId, string $actionType, ?string $field, ?string $old, ?string $new): void {
    $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, entity_type, entity_id, action_type, field_changed, old_value, new_value, change_timestamp)
    VALUES (:u,:e,:id,:a,:f,:o,:n,NOW())");
    $stmt->execute(['u'=>$userId,'e'=>$entityType,'id'=>$entityId,'a'=>$actionType,'f'=>$field,'o'=>$old,'n'=>$new]);
}

function uploadDir(): string { return __DIR__ . '/assets/images/merch/'; }

function processImage($file, $existing=null){
    if (($file['error']??4)===4) return [true,$existing,null];
    $dir = uploadDir();
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $name = preg_replace('/[^A-Za-z0-9._-]/','_',basename($file['name']));
    $target = $dir.$name;
    $rel = 'assets/images/merch/'.$name;
    if(file_exists($target)) return [false,$existing,'File exists'];
    if(!move_uploaded_file($file['tmp_name'],$target)) return [false,$existing,'Upload failed'];
    if($existing && $existing!==$rel){
        $old = __DIR__.'/'.$existing;
        if(is_file($old)) @unlink($old);
    }
    return [true,$rel,null];
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($_SESSION['csrf_token'],$_POST['csrf_token']??'')){
        setFlash('Invalid request','error'); header('Location: manage_merch.php'); exit;
    }

    $action=$_POST['action']??'';

    if($action==='create'){
        $name=trim($_POST['name']??'');
        $price=trim($_POST['price']??'');

        if($name===''||$price===''){ setFlash('Required fields missing','error'); }
        else{
            [$ok,$img,$err]=processImage($_FILES['image_path']??[]);
            if(!$ok){ setFlash($err,'error'); }
            else{
                $pdo->prepare("INSERT INTO merch(name,price,image_path,created_at,updated_at) VALUES(?,?,?,NOW(),NOW())")
                    ->execute([$name,$price,$img]);
                $id=$pdo->lastInsertId();
                writeAuditLog($pdo,$_SESSION['user_id'],'merch',$id,'CREATE','name',null,$name);
                writeAuditLog($pdo,$_SESSION['user_id'],'merch',$id,'CREATE','price',null,$price);
                setFlash('Created','success');
            }
        }
        header('Location: manage_merch.php'); exit;
    }

    if($action==='update'){
        $id=(int)$_POST['id'];
        $name=$_POST['name'];
        $price=$_POST['price'];

        $old=$pdo->prepare("SELECT * FROM merch WHERE merch_id=?");
        $old->execute([$id]);
        $old=$old->fetch();

        [$ok,$img,$err]=processImage($_FILES['image_path']??[],$old['image_path']);
        if(!$ok){ setFlash($err,'error'); }
        else{
            $pdo->prepare("UPDATE merch SET name=?,price=?,image_path=?,updated_at=NOW() WHERE merch_id=?")
                ->execute([$name,$price,$img,$id]);

            if($old['name']!=$name)
                writeAuditLog($pdo,$_SESSION['user_id'],'merch',$id,'UPDATE','name',$old['name'],$name);

            if($old['price']!=$price)
                writeAuditLog($pdo,$_SESSION['user_id'],'merch',$id,'UPDATE','price',$old['price'],$price);

            setFlash('Updated','success');
        }
        header('Location: manage_merch.php'); exit;
    }

    if($action==='delete'){
        $id=(int)$_POST['id'];
        $old=$pdo->prepare("SELECT * FROM merch WHERE merch_id=?");
        $old->execute([$id]);
        $old=$old->fetch();

        if($old){
            if($old['image_path']){
                $f=__DIR__.'/'.$old['image_path'];
                if(is_file($f)) @unlink($f);
            }
            $pdo->prepare("DELETE FROM merch WHERE merch_id=?")->execute([$id]);
            writeAuditLog($pdo,$_SESSION['user_id'],'merch',$id,'DELETE','name',$old['name'],null);
        }
        setFlash('Deleted','success');
        header('Location: manage_merch.php'); exit;
    }
}

$items=$pdo->query("SELECT * FROM merch ORDER BY created_at DESC")->fetchAll();
$flash=getFlash();
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Merch</title>
<style>
body{font-family:Arial;background:#f4f4f4}
.container{max-width:1000px;margin:50px auto}
.card{background:#fff;padding:20px;margin-bottom:20px;border-radius:10px}
.btn{background:#222;color:#fff;padding:10px;border-radius:6px;text-decoration:none}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px}
img{width:100px;height:100px;object-fit:cover}
</style>
</head>
<body>
<div class="container">
        <div class="top-bar">
            <a class="btn btn-primary" href="AdminDashboard.php">← Admin Dashboard</a>
        </div>

<?php if($flash): ?><div class="card"><?=htmlspecialchars($flash['message'])?></div><?php endif;?>

<div class="card">
<h2>Add Merch</h2>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="create">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input name="name" placeholder="Name" required>
<input name="price" placeholder="Price" required>
<input type="file" name="image_path">
<button>Save</button>
</form>
</div>

<div class="grid">
<?php foreach($items as $i): ?>
<div class="card">
<?php if($i['image_path']): ?><img src="/<?= $i['image_path'] ?>"><?php endif;?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?= $i['merch_id'] ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input name="name" value="<?= htmlspecialchars($i['name']) ?>">
<input name="price" value="<?= htmlspecialchars($i['price']) ?>">
<input type="file" name="image_path">
<button>Save</button>
</form>

<form method="POST">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $i['merch_id'] ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<button>Delete</button>
</form>
</div>
<?php endforeach;?>
</div>

</div>
</body>
</html>
