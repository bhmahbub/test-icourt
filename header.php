<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= ($logged_in) ? 'SaaS Dashboard' : 'Login' ?> - iCourt
    </title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='white' stroke='%23198754' stroke-width='2'/%3E%3Cg transform='translate(32,32) scale(-1,1) translate(-32,-32) translate(8,8) scale(0.075,0.075)'%3E%3Cpath fill='%23198754' d='M201.6 217.4L182.9 198.7C170.4 186.2 170.4 165.9 182.9 153.4L297.6 38.6C310.1 26.1 330.4 26.1 342.9 38.6L361.6 57.4C374.1 69.9 374.1 90.2 361.6 102.7L246.9 217.4C234.4 229.9 214.1 229.9 201.6 217.4zM308 275.7L276.6 244.3L388.6 132.3L508 251.7L396 363.7L364.6 332.3L132.6 564.3C117 579.9 91.7 579.9 76 564.3C60.3 548.7 60.4 523.4 76 507.7L308 275.7zM422.9 438.6C410.4 426.1 410.4 405.8 422.9 393.3L537.6 278.6C550.1 266.1 570.4 266.1 582.9 278.6L601.6 297.3C614.1 309.8 614.1 330.1 601.6 342.6L486.9 457.4C474.4 469.9 454.1 469.9 441.6 457.4L422.9 438.7z'/%3E%3C/g%3E%3C/svg%3E"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- CSS  -->
    <style>
        .dropdown-toggle::after {
            display: none;
        }

        :root {
            --primary: #198754;
            --primary-dark: #09492b;
            --primary-light: #4caf50;
            --secondary: #f57f17;
            --light: #f1f8e9;
            --dark: #1a237e;
            --text: #212121;
            --text-light: #757575;
            --white: #ffffff;
            --gray: #e0e0e0;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: var(--light);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Welcome banner */
        .welcome-banner {
            background: var(--primary);
            color: white;
            border-radius: 10px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        #welcomeBanner {
            opacity: 1;
            transition: opacity 0.5s ease-out;
            /* Smooth fade transition */
        }

        .welcome-banner::before,
        .welcome-banner::after {
            content: "";
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-banner::before {
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
        }

        .welcome-banner::after {
            bottom: -30px;
            right: 10%;
            width: 100px;
            height: 100px;
        }

        .welcome-banner h1 {
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
            line-height: 1.5;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: var(--white);
            border-radius: 0.5rem;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--light);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Table styles */
        .table-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            padding: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .table-details {
            background: var(--light);
            border-radius: 0.5rem;
            padding: 1.25rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .table {
            color: var(--text);
            overflow: hidden;
        }

        .table th {
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
            text-align: center;
        }

        .table td {
            vertical-align: middle;
            text-align: center;
        }

        /* Search styles */
        .search-wrapper {
            position: relative;
            max-width: 100%;
        }

        .search-input-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 0.5rem 0.5rem 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        .search-icon-container {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            align-items: center;
            padding-left: 0.75rem;
        }

        .search-icon {
            width: 1rem;
            height: 1rem;
            color: #9ca3af;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-yes {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-no {
            background-color: #dcfce7;
            color: #166534;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #4b5563;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .edit-btn:hover {
            background: #eff6ff;
            color: var(--primary-dark);
            border-color: #dbeafe;
        }

        .delete-btn:hover {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fee2e2;
        }


        .login-container {
            max-width: 1000px;
            width: 100%;
        }

        .login-card {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .brand-section {
            background:  var(--primary-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 1.25rem;
            text-align: center;
            color: var(--white);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }

        .login-btn {
            background: var(--primary);
            color: var(--white);
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
            width: 100%;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.4);
        }

        .copyright {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            margin-left: 0.75rem;
            border: 2px solid var(--primary);
            cursor: pointer;
            transition: var(--transition);
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            width: 200px;
            overflow: hidden;
            border: none;
        }

        .dropdown-item {
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: var(--light);
        }

        .footer {
            background: var(--white);
            padding: 1rem;
            border-top: 1px solid var(--gray);
            text-align: center;
            color: var(--text-light);
            font-size: 0.875rem;
            margin-top: auto;
        }

        .message-container {
            position: fixed;
            top: 5rem;
            right: 1rem;
            z-index: 9999;
            max-width: 400px;
            width: 100%;
        }

        .message {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateX(100%);
            animation: slideIn 0.5s forwards, fadeOut 0.5s 3.5s forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .add-btn {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            display: inline-flex;
            align-items: center;
            color: var(--primary-dark);
            background-color: var(--white);
            border: 1px solid var(--primary-light);
            font-size: 0.95rem;
            font-weight: bold;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.15);
            background-color: var(--primary);
            color: var(--white);
            cursor: pointer;
        }

        .empty-state {
            padding: 3rem 1.25rem;
            text-align: center;
        }

        .lg-screen {
            display: block;
        }

        .xs-screen {
            display: none;
        }


        @media screen and (max-width: 768px) {
            .lg-screen {
                display: none;
            }

            .xs-screen {
                display: block;
            }
        }
    </style>
</head>

<body>
    <!-- Toast Notification Container -->
    <?php if ($toast): ?>
        <div class="message-container">
            <div class="message <?= $toast['type'] === 'success' ? 'success-message' : 'error-message' ?>">
                <i class="fas <?= $toast['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($toast['message']) ?>
            </div>
        </div>
    <?php endif; ?>
