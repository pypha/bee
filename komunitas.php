<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

global $conn;

// Pastikan direktori upload ada dan bisa ditulisi
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Query untuk mendapatkan postingan
$query = "SELECT p.*, u.nama FROM postingan_komunitas p JOIN pengguna u ON p.user_id = u.id ORDER BY waktu DESC";
$result = mysqli_query($conn, $query);
$postingan = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle posting and file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unggah_postingan'])) {
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $user_id = $_SESSION['user_id'];
    $tipe = 'tulisan';
    $media_path = '';

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('img_', true) . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                $media_path = $target_path;
                $tipe = 'gambar';
            } else {
                $_SESSION['error'] = "Gagal mengupload file.";
            }
        } else {
            $_SESSION['error'] = "Format file tidak didukung. Hanya JPG, JPEG, PNG yang diperbolehkan.";
        }
    }

    // Gabungkan konten dan path media jika ada
    $konten_db = $konten;
    if (!empty($media_path)) {
        $konten_db .= '|' . $media_path;
    }

    // Insert ke database
    $query = "INSERT INTO postingan_komunitas (user_id, konten, tipe, waktu) VALUES ('$user_id', '$konten_db', '$tipe', NOW())";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Postingan berhasil ditambahkan!";
        header("Location: ?page=komunitas");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
}

// Handle editing
if (isset($_POST['simpan_edit'])) {
    $postingan_id = mysqli_real_escape_string($conn, $_POST['postingan_id']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    
    $query = "UPDATE postingan_komunitas SET konten='$konten', tipe='$tipe' WHERE id='$postingan_id' AND user_id='{$_SESSION['user_id']}'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Postingan berhasil diupdate!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: ?page=komunitas");
    exit();
}

// Handle deletion
if (isset($_GET['hapus_postingan'])) {
    $postingan_id = mysqli_real_escape_string($conn, $_GET['hapus_postingan']);
    
    // Dapatkan path gambar jika ada untuk dihapus
    $query = "SELECT konten FROM postingan_komunitas WHERE id='$postingan_id'";
    $result = mysqli_query($conn, $query);
    $post = mysqli_fetch_assoc($result);
    
    if ($post) {
        $content_parts = explode('|', $post['konten']);
        if (count($content_parts) > 1 && file_exists($content_parts[1])) {
            unlink($content_parts[1]);
        }
        
        $query = "DELETE FROM postingan_komunitas WHERE id='$postingan_id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Postingan berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    header("Location: ?page=komunitas");
    exit();
}
?>

<section class="py-10 bg-green-50">
    <h1 class="text-3xl font-bold mb-4 text-center">Komunitas Plantopia</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="max-w-2xl mx-auto mb-4 p-4 bg-red-100 text-red-700 rounded">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="max-w-2xl mx-auto mb-4 p-4 bg-green-100 text-green-700 rounded">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto mb-6 p-4 bg-white rounded-lg shadow">
        <textarea name="konten" class="border p-2 w-full rounded mb-2" placeholder="Tulis sesuatu..." required></textarea>
        <input type="file" name="file" class="border p-2 w-full rounded mb-2" accept="image/*">
        <button type="submit" name="unggah_postingan" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Unggah</button>
    </form>
    
    <?php foreach ($postingan as $post): ?>
        <div class="max-w-2xl mx-auto mb-4 p-4 bg-white rounded-lg shadow">
            <div class="flex items-center mb-2">
                <div class="w-10 h-10 bg-green-200 rounded-full flex items-center justify-center mr-2">
                    <span class="text-green-800 font-bold"><?php echo substr($post['nama'], 0, 1); ?></span>
                </div>
                <div>
                    <p class="font-semibold text-green-800"><?php echo htmlspecialchars($post['nama']); ?></p>
                    <p class="text-gray-500 text-sm"><?php echo date('d M Y H:i', strtotime($post['waktu'])); ?></p>
                </div>
            </div>
            
            <?php
            $content_parts = explode('|', $post['konten']);
            $text_content = htmlspecialchars($content_parts[0]);
            $media_path = isset($content_parts[1]) ? $content_parts[1] : '';
            
            echo "<p class='mt-2 mb-3'>$text_content</p>";
            
            if (!empty($media_path) && $post['tipe'] === 'gambar' && file_exists($media_path)) {
                echo "<img src='$media_path' alt='Posted Image' class='w-full max-h-96 object-contain mt-2 rounded border'>";
            }
            ?>
            
            <?php if ($post['user_id'] == $_SESSION['user_id'] || cekRoleAdmin()): ?>
                <div class="mt-3 pt-3 border-t">
                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                        <a href="?page=komunitas&edit_postingan=<?php echo $post['id']; ?>" class="text-blue-500 mr-3 hover:text-blue-700">Edit</a>
                    <?php endif; ?>
                    <a href="?page=komunitas&hapus_postingan=<?php echo $post['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Yakin ingin menghapus postingan ini?')">Hapus</a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['edit_postingan']) && $_GET['edit_postingan'] == $post['id'] && $post['user_id'] == $_SESSION['user_id']): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="postingan_id" value="<?php echo $post['id']; ?>">
                    <input type="hidden" name="tipe" value="<?php echo $post['tipe']; ?>">
                    <textarea name="konten" class="border p-2 w-full rounded mb-2" required><?php echo $text_content; ?></textarea>
                    <button type="submit" name="simpan_edit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Simpan Perubahan</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>