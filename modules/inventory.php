<?php
$pdo=db(); $user=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['add_item'])){
        $pdo->prepare('INSERT INTO inventory(item_name, category, unit, quantity, reorder_level, vendor_name) VALUES (?,?,?,?,?,?)')->execute([trim($_POST['item_name']),$_POST['category'],trim($_POST['unit']),(float)$_POST['quantity'],(float)$_POST['reorder_level'],trim($_POST['vendor_name'])]);
        flash('Inventory item added.');
    }
    if(isset($_POST['consume'])){
        $pdo->prepare('UPDATE inventory SET quantity=quantity-? WHERE id=?')->execute([(float)$_POST['used_qty'],(int)$_POST['id']]);
        $pdo->prepare('INSERT INTO inventory_transactions(inventory_id, txn_type, quantity, notes, created_by) VALUES (?,?,?,?,?)')->execute([(int)$_POST['id'],'consumption',(float)$_POST['used_qty'],trim($_POST['notes']),(int)$user['id']]);
        flash('Consumption recorded.');
    }
    if(isset($_POST['purchase'])){
        $pdo->prepare('UPDATE inventory SET quantity=quantity+? WHERE id=?')->execute([(float)$_POST['add_qty'],(int)$_POST['id']]);
        $pdo->prepare('INSERT INTO inventory_transactions(inventory_id, txn_type, quantity, notes, created_by) VALUES (?,?,?,?,?)')->execute([(int)$_POST['id'],'purchase',(float)$_POST['add_qty'],trim($_POST['notes']),(int)$user['id']]);
        flash('Purchase entry recorded.');
    }
    header('Location:/public/index.php?module=inventory'); exit;
}
$rows=$pdo->query('SELECT *, (quantity <= reorder_level) AS low_stock FROM inventory ORDER BY low_stock DESC, item_name')->fetchAll();
?>
<h4>Inventory (Diagnostics Consumables)</h4>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><input type="hidden" name="add_item" value="1"><div class="col-md-3"><input name="item_name" class="form-control" placeholder="Item" required></div><div class="col-md-2"><select name="category" class="form-select"><option>Consumable</option><option>Reagent</option></select></div><div class="col-md-1"><input name="unit" class="form-control" placeholder="Unit" required></div><div class="col-md-1"><input name="quantity" type="number" step="0.01" class="form-control" placeholder="Qty" required></div><div class="col-md-1"><input name="reorder_level" type="number" step="0.01" class="form-control" placeholder="ROL" required></div><div class="col-md-2"><input name="vendor_name" class="form-control" placeholder="Vendor"></div><div class="col-md-2"><button class="btn btn-primary">Add Item</button></div></form></div></div>
<table class="table table-sm table-striped"><tr><th>Item</th><th>Category</th><th>Qty</th><th>ROL</th><th>Vendor</th><th>Alert</th><th>Txn</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= e($r['item_name']) ?></td><td><?= e($r['category']) ?></td><td><?= money((float)$r['quantity']).' '.e($r['unit']) ?></td><td><?= money((float)$r['reorder_level']) ?></td><td><?= e((string)$r['vendor_name']) ?></td><td><?= $r['low_stock']?'<span class="badge text-bg-danger">Low Stock</span>':'' ?></td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input name="notes" class="form-control form-control-sm" placeholder="Notes"><input name="add_qty" type="number" step="0.01" class="form-control form-control-sm" placeholder="+Qty"><button name="purchase" value="1" class="btn btn-sm btn-outline-success">Purchase</button><input name="used_qty" type="number" step="0.01" class="form-control form-control-sm" placeholder="-Qty"><button name="consume" value="1" class="btn btn-sm btn-outline-warning">Consume</button></form></td></tr><?php endforeach; ?>
</table>
