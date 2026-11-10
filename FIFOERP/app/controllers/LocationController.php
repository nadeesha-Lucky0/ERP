<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Core\Logger;
use App\Models\Location;

class LocationController {
    private Location $locModel;

    public function __construct() {
        $this->locModel = new Location();
    }

    public function index(): void {
        Auth::requireLogin();
        $locations = $this->locModel->getAll();
        Response::json([
            'success' => true,
            'data' => $locations
        ]);
    }

    public function create(): void {
        Auth::requireAdmin();

        $csrfToken = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Session::validateCsrf($csrfToken)) {
            Response::error('Security token invalid or expired.', [], 403);
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = trim($_POST['type'] ?? 'Hydroelectric');
        $capacity = trim($_POST['capacity_mw'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($name)) {
            Response::error('Location / Power Station name is required.');
        }

        if (empty($code)) {
            $allLocs = $this->locModel->getAll();
            $maxNum = 830;
            foreach ($allLocs as $l) {
                if (is_numeric($l['code']) && (int)$l['code'] > $maxNum) {
                    $maxNum = (int)$l['code'];
                }
            }
            $code = (string)($maxNum + 1);
        }

        if ($this->locModel->isNameTaken($name)) {
            Response::error('A location/station with this name already exists.');
        }

        if ($this->locModel->isCodeTaken($code)) {
            Response::error('A location/station with code "' . $code . '" already exists.');
        }

        $id = $this->locModel->create([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'capacity_mw' => $capacity,
            'province' => $province,
            'district' => $district,
            'description' => $description,
            'status' => $status
        ]);

        Logger::log('create_location', 'location', $id, "Super Admin defined location: {$name} ({$code})");

        Response::success('Power Station / Location added successfully.', [
            'id' => $id,
            'location' => $this->locModel->findById($id)
        ]);
    }

    public function update(): void {
        Auth::requireAdmin();

        $csrfToken = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Session::validateCsrf($csrfToken)) {
            Response::error('Security token invalid or expired.', [], 403);
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id || !$this->locModel->findById($id)) {
            Response::error('Location not found.', [], 404);
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = trim($_POST['type'] ?? 'Hydroelectric');
        $capacity = trim($_POST['capacity_mw'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($name)) {
            Response::error('Location name is required.');
        }

        if ($this->locModel->isNameTaken($name, $id)) {
            Response::error('Location name is already in use by another station.');
        }

        if (!empty($code) && $this->locModel->isCodeTaken($code, $id)) {
            Response::error('Location code is already in use.');
        }

        $data = [
            'name' => $name,
            'type' => $type,
            'capacity_mw' => $capacity,
            'province' => $province,
            'district' => $district,
            'description' => $description,
            'status' => $status
        ];
        if (!empty($code)) {
            $data['code'] = $code;
        }

        $this->locModel->update($id, $data);
        Logger::log('update_location', 'location', $id, "Super Admin updated location: {$name}");

        Response::success('Location updated successfully.');
    }

    public function delete(): void {
        Auth::requireAdmin();

        $csrfToken = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Session::validateCsrf($csrfToken)) {
            Response::error('Security token invalid or expired.', [], 403);
        }

        $id = (int)($_POST['id'] ?? 0);
        $loc = $this->locModel->findById($id);

        if (!$loc) {
            Response::error('Location not found.', [], 404);
        }

        $this->locModel->delete($id);
        Logger::log('delete_location', 'location', $id, "Super Admin deleted location: {$loc['name']}");

        Response::success("Location '{$loc['name']}' deleted successfully.");
    }
}
