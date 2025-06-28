<?php
require_once 'config/db.php';

function sudahMasuk() {
    return isset($_SESSION['user_id']);
}

function tanganiDaftar($data) {
    global $conn;
    $nama = mysqli_real_escape_string($conn, $data['nama']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $sandi = password_hash($data['sandi'], PASSWORD_DEFAULT);
    $query = "INSERT INTO pengguna (nama, email, sandi, role) VALUES ('$nama', '$email', '$sandi', 'pengguna')";
    if (mysqli_query($conn, $query)) {
        // Kirim email verifikasi (PHPMailer diperlukan)
        header('Location: ?page=masuk');
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

function tanganiMasuk($data) {
    global $conn;
    $email = mysqli_real_escape_string($conn, $data['email']);
    $sandi = $data['sandi'];
    $query = "SELECT * FROM pengguna WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($sandi, $row['sandi'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            header('Location: ?page=beranda');
            exit;
        }
    }
    echo "Email atau kata sandi salah.";
}

function tanganiResetSandi($data) {
    // Implementasi PHPMailer untuk mengirim tautan reset sandi
    echo "Tautan reset kata sandi telah dikirim ke email Anda.";
}

function tambahKeKeranjang($data) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $tanaman_id = mysqli_real_escape_string($conn, $data['tanaman_id']);
    $jumlah = mysqli_real_escape_string($conn, $data['jumlah']);
    $query = "INSERT INTO keranjang (user_id, tanaman_id, jumlah) VALUES ('$user_id', '$tanaman_id', '$jumlah')";
    mysqli_query($conn, $query);
}

function updateKeranjang($data) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    foreach ($data['jumlah'] as $id => $jumlah) {
        $jumlah = mysqli_real_escape_string($conn, $jumlah);
        if ($jumlah == 0) {
            $query = "DELETE FROM keranjang WHERE id='$id' AND user_id='$user_id'";
        } else {
            $query = "UPDATE keranjang SET jumlah='$jumlah' WHERE id='$id' AND user_id='$user_id'";
        }
        mysqli_query($conn, $query);
    }
}

function buatPesanan($data) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $nama = mysqli_real_escape_string($conn, $data['nama']);
    $alamat = mysqli_real_escape_string($conn, $data['alamat']);
    $metode_pengiriman = mysqli_real_escape_string($conn, $data['metode_pengiriman']);
    $barcode = uniqid(); // Ganti dengan library QR code
    $query = "INSERT INTO pesanan (user_id, nama, alamat, metode_pengiriman, barcode) VALUES ('$user_id', '$nama', '$alamat', '$metode_pengiriman', '$barcode')";
    if (mysqli_query($conn, $query)) {
        $pesanan_id = mysqli_insert_id($conn);
        $keranjang = ambilKeranjang($user_id);
        foreach ($keranjang as $item) {
            $tanaman_id = $item['tanaman_id'];
            $jumlah = $item['jumlah'];
            $harga = $item['harga'];
            $query = "INSERT INTO detail_pesanan (pesanan_id, tanaman_id, jumlah, harga) VALUES ('$pesanan_id', '$tanaman_id', '$jumlah', '$harga')";
            mysqli_query($conn, $query);
            // Kurangi stok
            $query = "UPDATE tanaman SET stok = stok - $jumlah WHERE id = '$tanaman_id'";
            mysqli_query($conn, $query);
        }
        // Kosongkan keranjang
        $query = "DELETE FROM keranjang WHERE user_id='$user_id'";
        mysqli_query($conn, $query);
        // Kirim email konfirmasi (PHPMailer diperlukan)
        header('Location: ?page=pembayaran&pesanan_id=' . $pesanan_id);
        exit;
    }
}

function ambilKeranjang($user_id) {
    global $conn;
    $query = "SELECT k.id, k.tanaman_id, k.jumlah, t.harga, t.nama FROM keranjang k JOIN tanaman t ON k.tanaman_id = t.id WHERE k.user_id='$user_id'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function unggahPostingan($data) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $konten = mysqli_real_escape_string($conn, $data['konten']);
    $tipe = mysqli_real_escape_string($conn, $data['tipe']);
    $query = "INSERT INTO postingan_komunitas (user_id, konten, tipe) VALUES ('$user_id', '$konten', '$tipe')";
    mysqli_query($conn, $query);
}

function tambahKomentar($data) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $postingan_id = mysqli_real_escape_string($conn, $data['postingan_id']);
    $komentar = mysqli_real_escape_string($conn, $data['komentar']);
    $query = "INSERT INTO komentar (postingan_id, user_id, komentar) VALUES ('$postingan_id', '$user_id', '$komentar')";
    mysqli_query($conn, $query);
}

function updateProfil($data) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $nama = mysqli_real_escape_string($conn, $data['nama']);
    $alamat = mysqli_real_escape_string($conn, $data['alamat'] ?? '');
    $bio = mysqli_real_escape_string($conn, $data['bio'] ?? '');
    $no_telepon = mysqli_real_escape_string($conn, $data['no_telepon'] ?? '');
    $query = "UPDATE pengguna SET nama='$nama', alamat='$alamat', bio='$bio', no_telepon='$no_telepon' WHERE id='$user_id'";
    mysqli_query($conn, $query);
}

function tambahTanaman($data) {
    global $conn;
    $nama = mysqli_real_escape_string($conn, $data['nama']);
    $harga = mysqli_real_escape_string($conn, $data['harga']);
    $cara_menanam = mysqli_real_escape_string($conn, $data['cara_menanam']);
    $saran_tempat = mysqli_real_escape_string($conn, $data['saran_tempat']);
    $suhu = mysqli_real_escape_string($conn, $data['suhu']);
    $kelembapan = mysqli_real_escape_string($conn, $data['kelembapan']);
    $stok = mysqli_real_escape_string($conn, $data['stok']);
    $query = "INSERT INTO tanaman (nama, harga, cara_menanam, saran_tempat, suhu, kelembapan, stok) VALUES ('$nama', '$harga', '$cara_menanam', '$saran_tempat', '$suhu', '$kelembapan', '$stok')";
    mysqli_query($conn, $query);
}

function updateStatusPesanan($data) {
    global $conn;
    $pesanan_id = mysqli_real_escape_string($conn, $data['pesanan_id']);
    $status = mysqli_real_escape_string($conn, $data['status']);
    $query = "UPDATE pesanan SET status='$status' WHERE id='$pesanan_id'";
    mysqli_query($conn, $query);
}
function cekRoleAdmin() {
    if (!isset($_SESSION['user_id'])) return false;
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM pengguna WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        return $user['role'] === 'admin';
    }
    return false;
}
function keluar() {
    session_destroy();
}
?>