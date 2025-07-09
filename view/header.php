<?php
$userName = $_SESSION['nome_usuario'] ?? 'Aluno';
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudos Barroso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            transition: transform 0.3s ease;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        .navbar-brand img {
            height: 45px;
        }
        .user-dropdown-container .dropdown-menu {
            display: none;
            z-index: 1100;
            border-radius: .5rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            border: none;
            opacity: 0;
            background-color: #fff;
        }
        .nav-link.active {
            font-weight: 600;
            color: #0d6efd !important;
            position: relative;
        }
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 5px;
            height: 5px;
            background: #0d6efd;
            border-radius: 50%;
        }
        .navbar-toggler {
            transition: all 0.3s ease;
            border: none;
        }
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.15rem rgba(13,110,253,.25);
        }
        .user-avatar {
            transition: transform 0.3s ease, color 0.3s ease;
        }
        .user-avatar:hover, .user-avatar.show {
            transform: scale(1.1);
            color: #0d6efd;
        }
        .user-avatar .bi-caret-down-fill {
            transition: transform 0.3s ease;
            font-size: 0.8em;
        }
        .user-avatar.show .bi-caret-down-fill {
            transform: rotate(180deg);
        }
        @media (max-width: 991.98px) {
            .navbar-collapse {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                background: white;
                padding: 20px;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                z-index: 1020;
                opacity: 0;
                pointer-events: none;
                transform: translateY(-20px);
                max-height: calc(100vh - 70px);
                overflow-y: auto;
            }
            .navbar-collapse.show {
                pointer-events: auto;
            }
            .navbar-collapse.show .user-dropdown-container .dropdown-menu {
                position: static;
                display: grid;
                grid-template-rows: 0fr;
                transition: grid-template-rows 0.3s ease-in-out;
                background-color: transparent;
                box-shadow: none;
                border: none;
                border-top: 1px solid #e9ecef;
                border-radius: 0;
                padding: 0;
                margin-top: .75rem;
                opacity: 1;
            }
            .navbar-collapse.show .user-dropdown-container .dropdown-menu.show {
                display: grid;
               grid-template-rows: 1fr;
            }
            .navbar-collapse.show .user-dropdown-container .dropdown-menu > * {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>

<header class="sticky-top">
    <nav class="navbar navbar-expand-lg bg-white shadow-sm">
        <div class="container-fluid px-lg-4 px-3">
            <button class="navbar-toggler" type="button" id="mobileMenuButton" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item mx-lg-2"><a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard"><i class="bi bi-speedometer2 me-1"></i> Meus Resultados</a></li>
                    <li class="nav-item mx-lg-2"><a class="nav-link <?= $currentPage === 'questoes.php' ? 'active' : '' ?>" href="questoes"><i class="bi bi-journal-bookmark me-1"></i> Banco de Questões</a></li>
                </ul>
                <div class="d-flex flex-column flex-lg-row align-items-lg-center mt-3 mt-lg-0">
                    <div class="nav-item user-dropdown-container">
                        <a class="nav-link d-flex align-items-center p-0 user-avatar" href="#" id="userDropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-2"></i>
                            <span class="d-lg-none ms-2">Olá, <?php echo htmlspecialchars($userName); ?></span>
                            <i class="bi bi-caret-down-fill ms-1"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div>
                                    <h6 class="dropdown-header d-none d-lg-block">Olá, <?php echo htmlspecialchars($userName); ?>!</h6>
                                    <hr class="dropdown-divider">
                                    <a class="dropdown-item text-danger" href="../backend/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const navbarCollapse = document.getElementById('mainNavbar');
    const userDropdownToggle = document.getElementById('userDropdown');
    const userDropdownMenu = userDropdownToggle.nextElementSibling;
    const gerarProvaBtn = document.getElementById('gerarProvaBtn');
    let isUserDropdownOpen = false;

    function animateMobileMenu(show) {
        gsap.to(navbarCollapse, {
            opacity: show ? 1 : 0,
            y: show ? 0 : -20,
            duration: 0.3,
            ease: show ? "power2.out" : "power2.in",
            onStart: () => {
                if(show) navbarCollapse.classList.add('show');
            },
            onComplete: () => {
                mobileMenuButton.setAttribute('aria-expanded', String(show));
                if(!show) navbarCollapse.classList.remove('show');
            }
        });
    }

    function toggleUserDropdown(show) {
        if (show === isUserDropdownOpen) return;

        isUserDropdownOpen = show;
        userDropdownToggle.classList.toggle('show', show);
        userDropdownToggle.setAttribute('aria-expanded', String(show));

        const isMobileAccordion = window.innerWidth < 992 && navbarCollapse.classList.contains('show');

        if (isMobileAccordion) {
            if (show) {
        userDropdownMenu.classList.add('show');
    } else {
        userDropdownMenu.classList.remove('show');
    }
    return;
        }

        if (show) {
            userDropdownMenu.style.visibility = 'hidden';
            userDropdownMenu.style.display = 'block';

            const menuHeight = userDropdownMenu.offsetHeight;
            const menuWidth = userDropdownMenu.offsetWidth;
            
            userDropdownMenu.style.display = '';
            userDropdownMenu.style.visibility = '';

            const toggleRect = userDropdownToggle.getBoundingClientRect();
            let top = toggleRect.bottom + 8;
            let left = toggleRect.right - menuWidth;
            let initialY = -10;

            if ((top + menuHeight) > window.innerHeight) {
                top = toggleRect.top - menuHeight - 8;
                initialY = 10;
            }

            userDropdownMenu.style.position = 'fixed';
            userDropdownMenu.style.top = `${top}px`;
            userDropdownMenu.style.left = `${left}px`;
            userDropdownMenu.style.display = 'block';

            gsap.fromTo(userDropdownMenu, { y: initialY, opacity: 0 }, { y: 0, opacity: 1, duration: 0.3, ease: 'power2.out' });
        } else {
            gsap.to(userDropdownMenu, {
                opacity: 0,
                y: -10,
                duration: 0.2,
                ease: 'power2.in',
                onComplete: () => {
                    userDropdownMenu.style.display = 'none';
                    userDropdownMenu.style.position = '';
                    userDropdownMenu.style.top = '';
                    userDropdownMenu.style.left = '';
                }
            });
        }
    }

    mobileMenuButton.addEventListener('click', (e) => {
        e.stopPropagation();
        animateMobileMenu(!navbarCollapse.classList.contains('show'));
    });

    userDropdownToggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleUserDropdown(!isUserDropdownOpen);
    });

    if (gerarProvaBtn && gerarProvaBtn.getAttribute('href').startsWith('#')) {
        gerarProvaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (navbarCollapse.classList.contains('show')) {
                animateMobileMenu(false);
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (navbarCollapse.classList.contains('show') && !navbarCollapse.contains(e.target) && !mobileMenuButton.contains(e.target)) {
            animateMobileMenu(false);
        }
        if (isUserDropdownOpen && !userDropdownMenu.contains(e.target) && !userDropdownToggle.contains(e.target)) {
            toggleUserDropdown(false);
        }
    });
});
</script>
</body>
</html>