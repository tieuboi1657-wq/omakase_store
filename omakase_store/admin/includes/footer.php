</main>
</div>

<!-- Modal dùng chung -->
<div class="modal" id="commonModal">
  <div class="box" id="commonBox"></div>
</div>

<script>
// Tiện ích quản lý Modal
function openModal(html) {
  document.getElementById('commonBox').innerHTML = html;
  document.getElementById('commonModal').classList.add('open');
}

function closeModal() {
  document.getElementById('commonModal').classList.remove('open');
}

document.getElementById('commonModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeModal();
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeModal();
  }
});

// Hàm xuất CSV từ bảng HTML
function exportTableToCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const rows = [];
  for (let r of table.rows) {
    const row = [];
    for (let c of r.cells) {
      // Bỏ qua cột thao tác
      if (c.classList.contains('no-export')) continue;
      let text = c.innerText.replace(/"/g, '""').trim();
      row.push('"' + text + '"');
    }
    rows.push(row.join(','));
  }
  const csvContent = '\uFEFF' + rows.join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename || 'du-lieu.csv';
  link.click();
}
</script>
</body>
</html>
