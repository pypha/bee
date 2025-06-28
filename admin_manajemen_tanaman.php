<?php
global $conn;
$query = "SELECT * FROM tanaman";
$result = mysqli_query($conn, $query);
$tanaman = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<section class="py-10">
    <h1 class="text-3xl font-bold mb-4">Manajemen Tanaman</h1>
    <form method="POST" class="mb-6">
        <div class="mb-4">
            <label class="block mb-1">Nama Tanaman</label>
            <input type="text" name="nama" class="border p-2 w-full rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Harga</label>
            <input type="number" name="harga" class="border p-2 w-full rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Cara Menanam</label>
            <textarea name="cara_menanam" class="border p-2 w-full rounded" required></textarea>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Saran Tempat</label>
            <input type="text" name="saran_tempat" class="border p-2 w-full rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Suhu</label>
            <input type="text" name="suhu" class="border p-2 w-full rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Kelembapan</label>
            <input type="text" name="kelembapan" class="border p-2 w-full rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Stok</label>
            <input type="number" name="stok" class="border p-2 w-full rounded" required>
        </div>
        <button type="submit" name="tambah_tanaman" class="bg-green-500 text-white px-4 py-2 rounded">Tambah Tanaman</button>
    </form>
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-200">
                <th class="border p-2">Nama</th>
                <th class="border p-2">Harga</th>
                <th class="border p-2">Stok</th>
                <th class="border p-2">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tanaman as $item): ?>
                <tr>
                    <td class="border p-2"><?php echo htmlspecialchars($item['nama']); ?></td>
                    <td class="border p-2">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                    <td class="border p-2"><?php echo $item['stok']; ?></td>
                    <td class="border p-2">
                        <a href="?page=admin_manajemen_tanaman&edit=<?php echo $item['id']; ?>" class="text-blue-500">Edit</a>
                        <a href="?page=admin_manajemen_tanaman&hapus=<?php echo $item['id']; ?>" class="text-red-500">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>