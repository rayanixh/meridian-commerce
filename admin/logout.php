<?php
require_once __DIR__ . '/../config/config.php';
logout_admin();
redirect(base_url('admin/login.php'));
