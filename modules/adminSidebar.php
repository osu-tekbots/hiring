<!-- Sidebar -->
<ul class="sidebar navbar-nav">
    <!-- Template for dropdown sections -->
    <!-- <li class="nav-item dropdown">
            <a style="color: lightblue;" class="nav-link dropdown-toggle" href="#" id="pagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-fw fa-tools"></i>
                <span>Equipment</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="pagesDropdown">
                <a class="dropdown-item" href="pages/employeeEquipment.php">Overview</a>
                <div class="dropdown-divider"></div>
                <h6 class="dropdown-header">Adjust Content:</h6>
                <a class="dropdown-item" href="pages/employeeEquipmentList.php">Edit Equipment</a>
                <a class="dropdown-item" href="pages/employeeEquipmentMessages.php">Edit Messages</a>
                <a class="dropdown-item" href="pages/employeeEquipmentLabels.php">Print Labels</a>
            </div>
    </li> -->

    <li class="nav-item <?php echo ($active == 'Positions' ? 'active' : '') ?>">
        <a style="color: lightblue;" class="nav-link" href="pages/adminDashboard.php">
            <i class="fas fa-fw fa-user-tie"></i>
            <span>Positions</span>
        </a>
    </li>
    <li class="nav-item <?php echo ($active == 'Users' ? 'active' : '') ?>">
        <a style="color: lightblue;" class="nav-link" href="pages/adminUser.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Users</span>
        </a>
    </li>
    <li class="nav-item <?php echo ($active == 'Messages' ? 'active' : '') ?>">
        <a style="color: lightblue;" class="nav-link" href="pages/adminMessages.php">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Messages</span>
        </a>
    </li>
</ul>