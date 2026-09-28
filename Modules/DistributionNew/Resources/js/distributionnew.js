document.addEventListener('click', function (e) {
  if (e.target.closest('.disnew-add-line')) {
    var tbody = document.querySelector('.disnew-lines tbody'); if (!tbody) return;
    var i = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input name="lines['+i+'][product_id]" class="form-control"></td><td><input name="lines['+i+'][product_name]" class="form-control"></td><td><input name="lines['+i+'][qty]" class="form-control text-right"></td><td><input name="lines['+i+'][unit_price]" class="form-control text-right"></td><td><input name="lines['+i+'][discount]" class="form-control text-right"></td><td><input name="lines['+i+'][tax]" class="form-control text-right"></td><td><button type="button" class="btn btn-danger btn-xs disnew-remove-line">×</button></td>';
    tbody.appendChild(tr);
  }
  if (e.target.closest('.disnew-remove-line')) {
    var row = e.target.closest('tr'); if (row) row.remove();
  }
});
