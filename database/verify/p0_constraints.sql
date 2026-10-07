-- Verifikasi constraint skema P0 pada PostgreSQL (jalankan SETELAH migrasi, pada database lokal/test).
--   psql -d cafeapp -f database/verify/p0_constraints.sql
-- Seluruh skrip berjalan di dalam transaksi dan di-ROLLBACK di akhir, sehingga tidak meninggalkan data.
\set ON_ERROR_STOP on
BEGIN;

CREATE FUNCTION pg_temp.expect_error(label text, stmt text, expected_state text) RETURNS void AS $f$
BEGIN
    BEGIN
        EXECUTE stmt;
    EXCEPTION WHEN OTHERS THEN
        IF SQLSTATE = expected_state THEN
            RAISE NOTICE 'PASS  %', label;
            RETURN;
        END IF;
        RAISE EXCEPTION 'FAIL  % (diharapkan %, didapat % : %)', label, expected_state, SQLSTATE, SQLERRM;
    END;
    RAISE EXCEPTION 'FAIL  % (tidak ada error)', label;
END
$f$ LANGUAGE plpgsql;

CREATE FUNCTION pg_temp.expect_ok(label text, stmt text) RETURNS void AS $f$
BEGIN
    EXECUTE stmt;
    RAISE NOTICE 'PASS  %', label;
EXCEPTION WHEN OTHERS THEN
    RAISE EXCEPTION 'FAIL  % (error tak terduga % : %)', label, SQLSTATE, SQLERRM;
END
$f$ LANGUAGE plpgsql;

CREATE FUNCTION pg_temp.assert_true(label text, cond boolean) RETURNS void AS $f$
BEGIN
    IF cond IS TRUE THEN RAISE NOTICE 'PASS  %', label;
    ELSE RAISE EXCEPTION 'FAIL  %', label; END IF;
END
$f$ LANGUAGE plpgsql;

-- ---------------------------------------------------------------- data dasar
INSERT INTO users (id, name, email, password, role) VALUES
    (9001, 'Customer Uji', 'Customer.Uji@Cafe.test', 'x', 'customer'),
    (9002, 'Kasir Uji',    'kasir.uji@cafe.test',    'x', 'cashier'),
    (9003, 'Customer Dua', 'dua@cafe.test',          'x', 'customer');
INSERT INTO categories (id, name) VALUES (9001, 'Coffee Uji');
INSERT INTO products (id, category_id, name, price) VALUES (9001, 9001, 'Latte Uji', 27000);
INSERT INTO option_groups (id, name, is_required, min_selection, max_selection) VALUES (9001, 'Milk Uji', FALSE, 0, 1);
INSERT INTO options (id, option_group_id, name, price) VALUES (9001, 9001, 'Oat Milk', 4000), (9002, 9001, 'Extra Shot', 5000);
INSERT INTO product_option_groups (product_id, option_group_id) VALUES (9001, 9001);

-- ---------------------------------------------------------------- users
SELECT pg_temp.expect_error('users: role tidak valid ditolak',
    $q$INSERT INTO users (name,email,password,role) VALUES ('a','a1@x.test','x','admin')$q$, '23514');
SELECT pg_temp.expect_error('users: status tidak valid ditolak',
    $q$INSERT INTO users (name,email,password,status) VALUES ('a','a2@x.test','x','banned')$q$, '23514');
SELECT pg_temp.expect_error('users: email kembar (beda huruf besar/kecil) ditolak',
    $q$INSERT INTO users (name,email,password) VALUES ('a','customer.uji@cafe.test','x')$q$, '23505');
UPDATE users SET deleted_at = now() WHERE id = 9003;
SELECT pg_temp.expect_ok('users: email akun yang di-soft-delete dapat dipakai ulang',
    $q$INSERT INTO users (name,email,password) VALUES ('baru','dua@cafe.test','x')$q$);

-- ---------------------------------------------------------------- katalog
SELECT pg_temp.expect_error('products: harga negatif ditolak',
    $q$INSERT INTO products (category_id,name,price) VALUES (9001,'x',-1)$q$, '23514');
SELECT pg_temp.expect_error('products: status tidak valid ditolak',
    $q$INSERT INTO products (category_id,name,price,status) VALUES (9001,'x',1,'HABIS')$q$, '23514');
SELECT pg_temp.expect_error('products: stok negatif ditolak',
    $q$INSERT INTO products (category_id,name,price,stock) VALUES (9001,'x',1,-1)$q$, '23514');
SELECT pg_temp.expect_ok('products: stok NULL (tidak dilacak) diterima',
    $q$INSERT INTO products (category_id,name,price,stock) VALUES (9001,'y',1,NULL)$q$);
SELECT pg_temp.expect_error('categories: hapus kategori berisi produk ditolak (RESTRICT)',
    $q$DELETE FROM categories WHERE id = 9001$q$, '23503');
SELECT pg_temp.expect_error('categories: nama kembar ditolak',
    $q$INSERT INTO categories (name) VALUES ('coffee uji')$q$, '23505');
SELECT pg_temp.assert_true('products: pencarian ILIKE berfungsi',
    (SELECT count(*) FROM products WHERE name ILIKE '%latte%') = 1);

-- ---------------------------------------------------------------- customization
SELECT pg_temp.expect_error('option_groups: min > max ditolak',
    $q$INSERT INTO option_groups (name,min_selection,max_selection) VALUES ('g',3,2)$q$, '23514');
SELECT pg_temp.expect_error('option_groups: max = 0 ditolak',
    $q$INSERT INTO option_groups (name,min_selection,max_selection) VALUES ('g',0,0)$q$, '23514');
SELECT pg_temp.expect_error('option_groups: required dengan min 0 ditolak',
    $q$INSERT INTO option_groups (name,is_required,min_selection,max_selection) VALUES ('g',TRUE,0,1)$q$, '23514');
SELECT pg_temp.expect_ok('option_groups: required dengan min 1 diterima',
    $q$INSERT INTO option_groups (name,is_required,min_selection,max_selection) VALUES ('Size Uji',TRUE,1,1)$q$);
SELECT pg_temp.expect_error('options: nama kembar dalam satu group ditolak',
    $q$INSERT INTO options (option_group_id,name) VALUES (9001,'oat milk')$q$, '23505');
SELECT pg_temp.expect_error('options: harga tambahan negatif ditolak',
    $q$INSERT INTO options (option_group_id,name,price) VALUES (9001,'Minus',-5)$q$, '23514');
SELECT pg_temp.expect_error('option_groups: hapus group berisi option ditolak (RESTRICT)',
    $q$DELETE FROM option_groups WHERE id = 9001$q$, '23503');

-- ---------------------------------------------------------------- orders
INSERT INTO orders (id, order_number, daily_number, user_id, created_by_id, source, order_type, table_number,
                    subtotal, tax_percent, tax_amount, service_charge_percent, service_charge_amount, total,
                    idempotency_key)
VALUES (9001, 'CF-20261004-901', 901, 9001, 9001, 'customer', 'DINE_IN', '5',
        36000, 10, 3600, 5, 1800, 41400, 'key-1');

SELECT pg_temp.expect_error('orders: total tidak sesuai rumus ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,table_number,subtotal,tax_amount,service_charge_amount,total)
       VALUES ('CF-X1',1,9001,9001,'customer','DINE_IN','1',1000,0,0,999)$q$, '23514');
SELECT pg_temp.expect_error('orders: DINE_IN tanpa nomor meja ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,total)
       VALUES ('CF-X2',2,9001,9001,'customer','DINE_IN',1000,1000)$q$, '23514');
SELECT pg_temp.expect_ok('orders: TAKE_AWAY tanpa nomor meja diterima',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,total)
       VALUES ('CF-X3',3,9001,9001,'customer','TAKE_AWAY',1000,1000)$q$);
SELECT pg_temp.expect_error('orders: source customer tanpa user_id ditolak',
    $q$INSERT INTO orders (order_number,daily_number,created_by_id,source,order_type,subtotal,total)
       VALUES ('CF-X4',4,9001,'customer','TAKE_AWAY',1000,1000)$q$, '23514');
SELECT pg_temp.expect_ok('orders: pesanan walk-in kasir (user_id NULL) diterima',
    $q$INSERT INTO orders (order_number,daily_number,created_by_id,source,customer_name,order_type,subtotal,total,status)
       VALUES ('CF-X5',5,9002,'cashier','Budi','TAKE_AWAY',1000,1000,'PAID')$q$);
SELECT pg_temp.expect_error('orders: status tidak valid ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,total,status)
       VALUES ('CF-X6',6,9001,9001,'customer','TAKE_AWAY',1000,1000,'DONE')$q$, '23514');
SELECT pg_temp.expect_error('orders: CANCELLED tanpa cancelled_at ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,total,status)
       VALUES ('CF-X7',7,9001,9001,'customer','TAKE_AWAY',1000,1000,'CANCELLED')$q$, '23514');
SELECT pg_temp.expect_error('orders: diskon melebihi subtotal ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,discount_amount,total)
       VALUES ('CF-X8',8,9001,9001,'customer','TAKE_AWAY',1000,2000,0)$q$, '23514');
SELECT pg_temp.expect_error('orders: order_number kembar ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,total)
       VALUES ('CF-20261004-901',9,9001,9001,'customer','TAKE_AWAY',1000,1000)$q$, '23505');
SELECT pg_temp.expect_error('orders: idempotency key kembar dari pembuat yang sama ditolak',
    $q$INSERT INTO orders (order_number,daily_number,user_id,created_by_id,source,order_type,subtotal,total,idempotency_key)
       VALUES ('CF-X9',10,9001,9001,'customer','TAKE_AWAY',1000,1000,'key-1')$q$, '23505');
SELECT pg_temp.expect_ok('orders: idempotency key sama dari pembuat berbeda diterima',
    $q$INSERT INTO orders (order_number,daily_number,created_by_id,source,order_type,subtotal,total,idempotency_key)
       VALUES ('CF-X10',11,9002,'cashier','TAKE_AWAY',1000,1000,'key-1')$q$);
SELECT pg_temp.expect_error('users: hapus user yang punya order ditolak (RESTRICT)',
    $q$DELETE FROM users WHERE id = 9001$q$, '23503');

-- ---------------------------------------------------------------- order_items, order_item_options
INSERT INTO order_items (id, order_id, product_id, product_name, unit_price, quantity, options_price, subtotal, special_request)
VALUES (9001, 9001, 9001, 'Latte Uji', 27000, 1, 9000, 36000, 'Jangan terlalu manis');
INSERT INTO order_item_options (order_item_id, option_id, option_group_name, option_name, price)
VALUES (9001, 9001, 'Milk Uji', 'Oat Milk', 4000), (9001, 9002, 'Add-ons', 'Extra Shot', 5000);
INSERT INTO order_status_logs (order_id, from_status, to_status, changed_by_id) VALUES (9001, NULL, 'PENDING_PAYMENT', 9001);

SELECT pg_temp.expect_error('order_items: subtotal tidak sesuai rumus ditolak',
    $q$INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity,options_price,subtotal)
       VALUES (9001,9001,'x',1000,2,0,1999)$q$, '23514');
SELECT pg_temp.expect_error('order_items: quantity 0 ditolak',
    $q$INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity,options_price,subtotal)
       VALUES (9001,9001,'x',1000,0,0,0)$q$, '23514');
SELECT pg_temp.expect_error('order_items: special_request 251 karakter ditolak',
    format($q$INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity,subtotal,special_request)
       VALUES (9001,9001,'x',1000,1,1000,%L)$q$, repeat('a', 251)), '22001');
SELECT pg_temp.expect_ok('order_items: special_request tepat 250 karakter diterima',
    format($q$INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity,subtotal,special_request)
       VALUES (9001,9001,'x',1000,1,1000,%L)$q$, repeat('a', 250)));
SELECT pg_temp.expect_error('products: hapus produk yang pernah dipesan ditolak (RESTRICT)',
    $q$DELETE FROM products WHERE id = 9001$q$, '23503');

-- snapshot tetap utuh bila option master dihapus (SET NULL)
DELETE FROM options WHERE id = 9002;
SELECT pg_temp.assert_true('order_item_options: option dihapus -> option_id NULL, snapshot tetap ada',
    (SELECT option_id IS NULL AND option_name = 'Extra Shot' AND price = 5000
       FROM order_item_options WHERE order_item_id = 9001 AND option_group_name = 'Add-ons'));

-- ---------------------------------------------------------------- payments
INSERT INTO payments (id, order_id, method, status, amount, cash_received, received_by, paid_at)
VALUES (9001, 9001, 'CASH', 'PAID', 41400, 50000, 9002, now());
SELECT pg_temp.assert_true('payments: change_amount dihitung otomatis (50000 - 41400 = 8600)',
    (SELECT change_amount = 8600 FROM payments WHERE id = 9001));
SELECT pg_temp.expect_error('payments: dua pembayaran PAID untuk satu order ditolak',
    $q$INSERT INTO payments (order_id,method,status,amount,paid_at) VALUES (9001,'QRIS','PAID',41400,now())$q$, '23505');
SELECT pg_temp.expect_ok('payments: percobaan gagal/pending tambahan diperbolehkan',
    $q$INSERT INTO payments (order_id,method,status,amount) VALUES (9001,'QRIS','FAILED',41400),(9001,'QRIS','PENDING',41400)$q$);
SELECT pg_temp.expect_error('payments: tunai kurang dari total ditolak',
    $q$INSERT INTO payments (order_id,method,status,amount,cash_received) VALUES (9001,'CASH','PENDING',41400,1000)$q$, '23514');
SELECT pg_temp.expect_error('payments: status PAID tanpa paid_at ditolak',
    $q$INSERT INTO payments (order_id,method,status,amount) VALUES (9001,'CASH','PAID',100)$q$, '23514');
SELECT pg_temp.expect_error('payments: amount 0 ditolak',
    $q$INSERT INTO payments (order_id,method,status,amount) VALUES (9001,'CASH','PENDING',0)$q$, '23514');
SELECT pg_temp.expect_error('payments: metode tidak valid ditolak',
    $q$INSERT INTO payments (order_id,method,status,amount) VALUES (9001,'CRYPTO','PENDING',100)$q$, '23514');

-- ---------------------------------------------------------------- order_counters, settings
CREATE TEMP TABLE _ctr (n int);
WITH r AS (
    INSERT INTO order_counters (business_date, last_number) VALUES ('2099-01-01', 1)
    ON CONFLICT (business_date) DO UPDATE SET last_number = order_counters.last_number + 1
    RETURNING last_number)
INSERT INTO _ctr SELECT last_number FROM r;
WITH r AS (
    INSERT INTO order_counters (business_date, last_number) VALUES ('2099-01-01', 1)
    ON CONFLICT (business_date) DO UPDATE SET last_number = order_counters.last_number + 1
    RETURNING last_number)
INSERT INTO _ctr SELECT last_number FROM r;
SELECT pg_temp.assert_true('order_counters: upsert atomik menghasilkan 1 lalu 2',
    (SELECT array_agg(n ORDER BY n) FROM _ctr) = ARRAY[1,2]);

SELECT pg_temp.expect_ok('settings: nilai jsonb diterima',
    $q$INSERT INTO settings (key, value) VALUES ('tax_percent_uji', '10'), ('cafe_name_uji', '"CafeApp"')$q$);

-- ---------------------------------------------------------------- query kunci (DATABASE_SCHEMA bagian 6)
SELECT pg_temp.assert_true('query konsistensi options_price: 0 baris',
    (SELECT count(*) FROM (
        SELECT oi.id FROM order_items oi
        LEFT JOIN order_item_options oio ON oio.order_item_id = oi.id
        WHERE oi.id = 9001
        GROUP BY oi.id, oi.options_price
        HAVING oi.options_price <> COALESCE(SUM(oio.price * oio.quantity), 0)) z) = 0);

-- Antrean Kitchen harus memuat customization + special request dan TIDAK memuat harga
UPDATE orders SET status = 'CONFIRMED', confirmed_at = now() WHERE id = 9001;
CREATE TEMP TABLE _kitchen AS
SELECT o.id, o.daily_number,
    (SELECT json_agg(json_build_object(
        'item_id', oi.id, 'name', oi.product_name, 'quantity', oi.quantity,
        'special_request', oi.special_request,
        'options', (SELECT COALESCE(json_agg(json_build_object('group', oio.option_group_name,
                          'option', oio.option_name, 'quantity', oio.quantity) ORDER BY oio.id), '[]'::json)
                    FROM order_item_options oio WHERE oio.order_item_id = oi.id)
    ) ORDER BY oi.id) FROM order_items oi WHERE oi.order_id = o.id AND oi.id = 9001) AS items
FROM orders o WHERE o.status IN ('CONFIRMED','PREPARING','READY') AND o.id = 9001;
SELECT pg_temp.assert_true('kitchen: special request tampil utuh',
    (SELECT items::text LIKE '%Jangan terlalu manis%' FROM _kitchen));
SELECT pg_temp.assert_true('kitchen: option (group + nama) tampil',
    (SELECT items::text LIKE '%Milk Uji%' AND items::text LIKE '%Oat Milk%' AND items::text LIKE '%Extra Shot%' FROM _kitchen));
SELECT pg_temp.assert_true('kitchen: tidak ada field harga',
    (SELECT items::text NOT LIKE '%price%' AND items::text NOT LIKE '%unit_price%' FROM _kitchen));
SELECT pg_temp.assert_true('index antrean kitchen bersifat parsial',
    (SELECT indexdef LIKE '%WHERE%CONFIRMED%' FROM pg_indexes WHERE indexname = 'orders_kitchen_queue_idx'));

-- ---------------------------------------------------------------- cascade dan restrict
SELECT pg_temp.expect_error('payments: hapus order yang punya pembayaran ditolak (RESTRICT)',
    $q$DELETE FROM orders WHERE id = 9001$q$, '23503');
DELETE FROM payments WHERE order_id = 9001;
DELETE FROM orders WHERE id = 9001;
SELECT pg_temp.assert_true('cascade: hapus order menghapus item, option, dan log',
    (SELECT count(*) FROM order_items WHERE order_id = 9001) = 0
    AND (SELECT count(*) FROM order_item_options WHERE order_item_id = 9001) = 0
    AND (SELECT count(*) FROM order_status_logs WHERE order_id = 9001) = 0);

ROLLBACK;
