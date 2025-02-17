<aside id="sidebar" class="expand">
    <div class="d-flex">
        <button class="toggle-btn" type="button">
            <i class="lni lni-menu"></i>
        </button>
        <div class="sidebar-logo">
            <a href="#">MyCourseReads</a>
        </div>
    </div>

    <ul class="sidebar-nav">

        <li class="sidebar-item">
            <a href="dashboards.php" class="sidebar-link">
                <i class="lni lni-grid-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#programs"
                aria-expanded="false" aria-controls="subjects">
                <i class="lni lni-graduation"></i>
                <span>Programs</span>
            </a>
            <ul id="programs" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                    <a href="programs.php" class="sidebar-link">Add Program</a>
                </li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#subjects"
                aria-expanded="false" aria-controls="subjects">
                <i class="lni lni-ruler-pencil"></i>
                <span>Courses</span>
            </a>
            <ul id="subjects" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                    <a href="subjectsadd.php" class="sidebar-link">Add Course</a>
                </li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse"
                data-bs-target="#collections" aria-expanded="false" aria-controls="collections">
                <i class="lni lni-book"></i>
                <span>Collections</span>
            </a>
            <ul id="collections" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                    <a href="booksadd.php" class="sidebar-link">Add Collection (Vivliotek)</a>
                </li>
                <li class="sidebar-item">
                    <a href="booksfetch.php" class="sidebar-link">Add Collection (API)</a>
                </li>
                <li class="sidebar-item">
                    <a href="books.php" class="sidebar-link">Add Collection</a>
                </li>
                <li class="sidebar-item">
                    <a href="materials.php" class="sidebar-link">Add Material</a>
                </li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#reports"
                aria-expanded="false" aria-controls="reports">
                <i class="lni lni-layers"></i>
                <span>Reports</span>
            </a>
            <ul id="reports" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                    <a href="checklist.php" class="sidebar-link">Programs & Courses</a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php" class="sidebar-link">Reference Materials</a>
                </li>
                <li class="sidebar-item">
                    <a href="mapping.php" class="sidebar-link">Collection Mapping</a>
                </li>
            </ul>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#users"
                aria-expanded="false" aria-controls="users">
                <i class="lni lni-users"></i>
                <span>Users</span>
            </a>
            <ul id="users" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                    <a href="userlibrarian.php" class="sidebar-link">Librarians</a>
                </li>
                <li class="sidebar-item">
                    <a href="userstaff.php" class="sidebar-link">Library staff</a>
                </li>
                <li class="sidebar-item">
                    <a href="userstudent.php" class="sidebar-link">Students</a>
                </li>
                <li class="sidebar-item">
                    <a href="userfaculty.php" class="sidebar-link">Faculty</a>
                </li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#settings"
                aria-expanded="false" aria-controls="settings">
                <i class="lni lni-cog"></i>
                <span>Settings</span>
            </a>
            <ul id="settings" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                    <a href="recovery.php" class="sidebar-link">Data Recovery</a>
                </li>
            </ul>
        </li>

    </ul>

    <div class="sidebar-footer">
        <a href="../adminlogin.php" class="sidebar-link">
            <i class="lni lni-exit"></i>
            <span>Logout</span>
        </a>
    </div>

</aside>