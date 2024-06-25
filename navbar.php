<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$path = parse_url($current_url, PHP_URL_PATH);
$filename = pathinfo($path, PATHINFO_FILENAME);

$username = $_SESSION['user']['email'] ?? null;
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light mb-2">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand" href="https://www.dreamtrender.com/"><img src="https://www.dreamtrender.com/img/6.png" class="navlogo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 ml-auto">
                <li class="nav-item"><a class="nav-link <?php echo ($filename == 'index') ? 'active' : ''; ?>" aria-current="page" href="https://www.dreamtrender.com/">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="https://www.dreamtrender.com/about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="https://www.dreamtrender.com/contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($filename == 'cart') ? 'active' : ''; ?>" href="https://www.dreamtrender.com/user/cart"><img src="https://www.dreamtrender.com/img/cart.png" alt="Cart" class="register-icon"></a></li>
                <?php if ($_SESSION['user']) : ?>

                    <?php if ($_SESSION['user']['privilege'] >= 2) : ?>
                        <li class="nav-item"><a class="nav-link <?php echo ($filename == 'create-item') ? 'active' : ''; ?>" href="https://www.dreamtrender.com/admin/create-item">New item</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($filename == 'profile') ? 'active' : ''; ?>" href="https://www.dreamtrender.com/user/profile"><img src="https://www.dreamtrender.com/img/register.png" alt="Profile" class="register-icon"></a></li>
                    <li class="nav-item"><a class="nav-link" href="https://www.dreamtrender.com/user/logout"><img src="https://www.dreamtrender.com/img/logout.png" alt="Logout" class="register-icon"></a></li>
                <?php else : ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($filename == 'register') ? 'active' : ''; ?>" href="https://www.dreamtrender.com/user/register"><img src="https://www.dreamtrender.com/img/register.png" alt="Register" class="register-icon"></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
