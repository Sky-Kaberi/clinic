<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Clinic Patients</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; padding: 0 12px; }
        h1 { margin-bottom: 16px; }
        form { display: grid; gap: 10px; margin-bottom: 20px; }
        input, button { padding: 10px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f3f3f3; }
        .status { margin: 12px 0; }
    </style>
</head>
<body>
    <h1>Clinic Patient Register</h1>

    <form id="patient-form">
        <input type="text" name="full_name" placeholder="Full name" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="text" name="phone" placeholder="Phone" />
        <button type="submit">Add Patient</button>
    </form>

    <div id="status" class="status"></div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody id="patient-list"></tbody>
    </table>

    <script src="assets/app.js"></script>
</body>
</html>
