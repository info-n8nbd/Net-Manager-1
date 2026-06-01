<?php

return [
    'dashboard' => [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'description' => 'View your ISP business overview at a glance.',
        'guide' => 'Once you log in, the Dashboard shows a complete snapshot of your business — total clients, online clients, today\'s collection, monthly revenue, due bills, expenses, and more. Real-time charts and graphs help you make quick decisions.',
        'submenus' => [
            'admin.dashboard' => [
                'title' => 'Dashboard',
                'content' => "**Dashboard Page** — the central hub of your ISP business.\n\n"
                    . "**Top Summary Cards:**\n"
                    . "• Total Clients — number of all registered clients\n"
                    . "• Online Clients — currently connected users\n"
                    . "• Active / Inactive — count of enabled and disabled connections\n"
                    . "• Today's Collection — amount collected today\n"
                    . "• Monthly Collection — total collection for the current month\n"
                    . "• Due Bills — unpaid bills\n\n"
                    . "**Charts & Graphs:**\n"
                    . "• 30-day new client trend\n"
                    . "• 30-day collection trend\n"
                    . "• Client distribution by zone\n"
                    . "• Monthly revenue comparison\n\n"
                    . "**Recent Activity:**\n"
                    . "• Recent payments\n"
                    . "• Recent tickets\n"
                    . "• Clients expiring soon\n"
                    . "• Staff dues",
            ],
        ],
    ],

    'client' => [
        'title' => 'Client Management',
        'icon' => 'fas fa-users',
        'description' => 'Add, edit, and manage client connections all in one place.',
        'guide' => 'In the Client module you can add new clients, view and edit existing client information, and manage their connections. Clients can also view their own details through the Client Portal.',
        'submenus' => [
            'admin.applications.index' => [
                'title' => 'New Request',
                'content' => "**New Request** — view new connection applications from clients.\n\n"
                    . "**How it works:**\n"
                    . "1. A client or staff member submits a connection application\n"
                    . "2. The application appears here with Pending status\n"
                    . "3. Admin reviews and approves or rejects it\n"
                    . "4. On approval, the client account is created automatically\n\n"
                    . "**Statuses:**\n"
                    . "• Pending — awaiting review\n"
                    . "• Approved — accepted\n"
                    . "• Rejected — declined",
            ],
            'admin.applications.create' => [
                'title' => 'Submit Application',
                'content' => "**Submit Application** — fill in the form for a new client connection.\n\n"
                    . "**Form Steps:**\n"
                    . "1. Enter client name, mobile, and address\n"
                    . "2. Select zone/area\n"
                    . "3. Choose a package\n"
                    . "4. Select connection type\n"
                    . "5. Submit\n\n"
                    . "After submission the application will appear in the 'New Request' list.",
            ],
            'admin.clients.create' => [
                'title' => 'Add New Client',
                'content' => "**Add New Client** — create a client directly without an application.\n\n"
                    . "**Required Information:**\n"
                    . "• Name, mobile number, address\n"
                    . "• Zone and area\n"
                    . "• Package and connection type\n"
                    . "• PPPoE username & password (or auto-generate)\n"
                    . "• Select router\n"
                    . "• Set billing date\n\n"
                    . "Once created, the system will automatically create the user on the MikroTik router.",
            ],
            'admin.clients.index' => [
                'title' => 'Client List',
                'content' => "**Client List** — all active clients.\n\n"
                    . "**Features:**\n"
                    . "• Search — by name, mobile, username, or ID\n"
                    . "• Filter — by zone, area, package, status, connection type, MikroTik status (Enabled/Disabled)\n"
                    . "• Export — download the filtered list as CSV\n\n"
                    . "**Per-client actions:**\n"
                    . "• View — see full details\n"
                    . "• Edit — update information\n"
                    . "• Enable/Disable connection\n"
                    . "• Generate bill & collect payment\n\n"
                    . "**Bulk Actions (select multiple clients):**\n"
                    . "• Activate / Deactivate — change status in bulk\n"
                    . "• Change Package — assign a new package to all selected\n"
                    . "• Change Zone / Sub Zone / TJ Box — move clients to a different zone, area, or box\n"
                    . "• MikroTik Enable — restore active MikroTik profile\n"
                    . "• MikroTik Disable — set MikroTik profile to Inactive\n"
                    . "• MikroTik Sync — push software profile to the MikroTik router (fixes mismatches)\n"
                    . "• Export — download selected clients as CSV",
            ],
            'admin.clients.inactive' => [
                'title' => 'Left Client',
                'content' => "**Left Client** — clients whose connections have been cancelled or disabled.\n\n"
                    . "This list shows clients who:\n"
                    . "• Had their connection terminated\n"
                    . "• Were auto-disabled due to overdue bills (regular clients)\n"
                    . "• Were auto-disabled due to expired recharge (reseller clients)\n"
                    . "• Cancelled on their own\n\n"
                    . "**Disable reasons:**\n"
                    . "• 'Inactive for due payment' — regular client overdue\n"
                    . "• 'Recharge expired' — reseller client expired\n\n"
                    . "You can reactivate clients from here. MikroTik profile is restored automatically on reactivation.",
            ],
            'admin.client.scheduler' => [
                'title' => 'Scheduler',
                'content' => "**Scheduler** — set future activation/deactivation schedules.\n\n"
                    . "**Usage:**\n"
                    . "• Schedule connection activation on a specific date\n"
                    . "• Schedule connection deactivation on a specific date\n"
                    . "• Schedule package changes",
            ],
            'admin.clients.package-requests' => [
                'title' => 'Change Request',
                'content' => "**Change Request** — package change requests submitted by clients through the portal.\n\n"
                    . "• View which package the client wants to switch from and to\n"
                    . "• Approve to automatically apply the change\n"
                    . "• Reject to notify the client",
            ],
            'admin.client.portal' => [
                'title' => 'Portal Manage',
                'content' => "**Portal Manage** — settings for the client portal.\n\n"
                    . "The client portal is a web page where clients can:\n"
                    . "• View their bills\n"
                    . "• Pay online\n"
                    . "• Request package changes\n"
                    . "• Open support tickets\n\n"
                    . "Enable or disable portal features from this page.",
            ],
            'admin.client-recovery.index' => [
                'title' => 'Client Restore',
                'content' => "**Client Restore** — recover accidentally deleted clients.\n\n"
                    . "Deleted clients can be restored within a defined retention period.",
            ],
        ],
    ],

    'billing' => [
        'title' => 'Billing',
        'icon' => 'fas fa-money-check-alt',
        'description' => 'Generate bills, collect payments, and manage dues.',
        'guide' => 'In the Billing module you can generate monthly bills, collect payments, track dues, and configure billing automation.',
        'submenus' => [
            'admin.billing.generate' => [
                'title' => 'Generate Bills',
                'content' => "**Generate Bills** — create monthly bills.\n\n"
                    . "**How to:**\n"
                    . "1. Select month and year\n"
                    . "2. Choose whether to generate for all clients or a specific zone\n"
                    . "3. Click 'Generate Bills'\n\n"
                    . "The system creates a bill for every active client based on their package price. Previous months' dues are carried forward.\n\n"
                    . "**Note:** Generating bills twice for the same month will not create duplicates.",
            ],
            'admin.billing.index' => [
                'title' => 'Bill List',
                'content' => "**Bill List** — all generated bills.\n\n"
                    . "• Filter by month/year\n"
                    . "• Status filter: Paid, Unpaid, Partial\n"
                    . "• Collect payment directly from any bill\n"
                    . "• Print and download bills",
            ],
            'admin.billing.due' => [
                'title' => 'Due Bills',
                'content' => "**Due Bills** — bills that remain unpaid.\n\n"
                    . "• View dues by client\n"
                    . "• Filter by zone\n"
                    . "• Send SMS/WhatsApp reminders\n"
                    . "• Disable connection for overdue clients",
            ],
            'admin.payments.index' => [
                'title' => 'Payments',
                'content' => "**Payments** — all payment records.\n\n"
                    . "**Ways to collect payment:**\n"
                    . "• Click 'Pay' from the Bill List\n"
                    . "• Collect from the client profile\n"
                    . "• Automatic via online payment gateway\n\n"
                    . "**Payment Methods:**\n"
                    . "• Cash, bKash, Nagad, Rocket, Bank Transfer, etc.",
            ],
            'admin.payments.pending' => [
                'title' => 'Pending Payments',
                'content' => "**Pending Payments** — payments awaiting confirmation.\n\n"
                    . "Online payments or staff-collected payments that haven't been confirmed yet.\n\n"
                    . "• Approve — confirm the payment\n"
                    . "• Reject — decline the payment",
            ],
            'admin.billing.collection-transfers' => [
                'title' => 'Collection Transfer',
                'content' => "**Collection Transfer** — record of field staff depositing collected money at the office.\n\n"
                    . "Staff members collect payments in the field and deposit them at the office — track those deposits here.",
            ],
            'admin.billing.advance-collection' => [
                'title' => 'Advance Collection',
                'content' => "**Advance Collection** — collect future months' bills in advance.\n\n"
                    . "When a client wants to pay upcoming months ahead of time:\n\n"
                    . "• Select the client\n"
                    . "• Set how many months in advance\n"
                    . "• Select payment method",
            ],
            'admin.billing.online-payments' => [
                'title' => 'Online Payments',
                'content' => "**Online Payments** — payments received via online gateways.\n\n"
                    . "View and verify payments from bKash, Nagad, SSLCommerz, and other gateways.",
            ],
            'admin.corrections.index' => [
                'title' => 'Bill/Payment Corrections',
                'content' => "**Bill/Payment Corrections** — fix incorrect bills or payments.\n\n"
                    . "If a bill amount is wrong or a payment was recorded incorrectly, submit a correction here. A reason must be provided for every correction.",
            ],
            'billing.automation.settings' => [
                'title' => 'Automation Settings',
                'content' => "**Automation Settings** — billing automation configuration.\n\n"
                    . "**Settings:**\n"
                    . "• Auto Bill Generation — bills are created automatically every month\n"
                    . "• Auto SMS Reminder — SMS sent before/after bill due date\n"
                    . "• Auto Connection Disable — connection disabled after a set number of overdue days\n"
                    . "• Late Fee — extra charge for late payments\n\n"
                    . "**Auto-disable behavior:**\n"
                    . "• Regular clients — disabled when due amount > 0 and payment deadline has passed\n"
                    . "• MAC Reseller clients — disabled solely based on Expire Date (not billing/payment deadline)\n"
                    . "• When auto-disabled, the MikroTik profile is set to 'Inactive' and the original profile is saved for restoration",
            ],
            'admin.staff-dues.index' => [
                'title' => 'Staff Dues',
                'content' => "**Staff Dues** — money held by staff members.\n\n"
                    . "Staff collect payments from clients but haven't deposited them at the office yet.\n\n"
                    . "• View how much each staff member is holding\n"
                    . "• See detailed transactions",
            ],
        ],
    ],

    'reports' => [
        'title' => 'Reports',
        'icon' => 'fas fa-chart-pie',
        'description' => 'Revenue, collection, due, zone-wise, and other reports.',
        'guide' => 'The Reports module lets you generate and download various business reports.',
        'submenus' => [
            'admin.reports.income-statement' => [
                'title' => 'Income Statement',
                'content' => "**Income Statement** — summary of total income and expenses for a given period.\n\n"
                    . "Select a date range to view a complete income/expense breakdown. Download as PDF or Excel.",
            ],
            'admin.reports.daily' => [
                'title' => 'Daily Collection',
                'content' => "**Daily Collection** — report of how much was collected each day.\n\n"
                    . "• Select a date\n"
                    . "• View collection by staff\n"
                    . "• Breakdown by payment method",
            ],
            'admin.reports.monthly' => [
                'title' => 'Monthly Revenue',
                'content' => "**Monthly Revenue** — month-over-month revenue comparison.\n\n"
                    . "Compare collections, dues, and new clients across multiple months.",
            ],
            'admin.reports.due' => [
                'title' => 'Due Report',
                'content' => "**Due Report** — detailed list of all clients with outstanding balances.\n\n"
                    . "• View dues by zone/area\n"
                    . "• See how many months overdue\n"
                    . "• Send SMS reminders directly",
            ],
            'admin.reports.client-ledger' => [
                'title' => 'Client Ledger',
                'content' => "**Client Ledger** — full transaction history for a specific client.\n\n"
                    . "Select a client to see all their bills and payment details.",
            ],
            'admin.reports.zone' => [
                'title' => 'Zone Report',
                'content' => "**Zone Report** — collection, dues, and client count by zone.\n\n"
                    . "See which zone has how many clients, how much has been collected, and how much is due — all in one place.",
            ],
            'admin.reports.employee-collection' => [
                'title' => 'Employee Collection',
                'content' => "**Employee Collection** — how much each staff member has collected.\n\n"
                    . "Track staff performance — who collected how much and how much they deposited.",
            ],
            'admin.reports.profit-loss' => [
                'title' => 'Profit & Loss',
                'content' => "**Profit & Loss Statement** — overall business profit or loss.\n\n"
                    . "Compare income against expenses to see net profit/loss.",
            ],
            'admin.reports.btrc-format' => [
                'title' => 'BTRC Report',
                'content' => "**BTRC Report** — regulatory report required by the Bangladesh Telecommunication Regulatory Commission (BTRC).\n\n"
                    . "Download the report in BTRC's prescribed format.",
            ],
            'admin.reports.stock' => [
                'title' => 'Stock Report',
                'content' => "**Stock Report** — current stock status of inventory items.",
            ],
            'admin.reports.bandwidth' => [
                'title' => 'Bandwidth Report',
                'content' => "**Bandwidth Report** — detailed bandwidth usage.\n\n"
                    . "See how much bandwidth each client or zone is consuming.",
            ],
        ],
    ],

    'configuration' => [
        'title' => 'Configuration',
        'icon' => 'fas fa-sliders-h',
        'description' => 'Set up areas, connection types, packages, and dropdown options.',
        'guide' => 'Configure the basic setup of your ISP — areas, packages, connection types, etc. These must be configured before you can add clients.',
        'submenus' => [
            'admin.config.area' => [
                'title' => 'Area',
                'content' => "**Area** — define your service areas.\n\n"
                    . "Create areas under each zone. You must select an area when adding a client.\n\n"
                    . "• Add new area\n"
                    . "• Edit area name\n"
                    . "• Delete area (only if no clients are assigned)",
            ],
            'admin.config.connection-type' => [
                'title' => 'Connection Type',
                'content' => "**Connection Type** — set up different connection types.\n\n"
                    . "Examples: Fiber, Wireless, Cable, etc. This is selected when adding a client.",
            ],
            'admin.config.client-type' => [
                'title' => 'Client Type',
                'content' => "**Client Type** — define client categories.\n\n"
                    . "Examples: Residential, Commercial, Corporate, etc.",
            ],
            'admin.settings.packages' => [
                'title' => 'Package & Profile',
                'content' => "**Package & Profile** — create and edit internet packages.\n\n"
                    . "**For each package, configure:**\n"
                    . "• Package name (e.g., 10 Mbps, 20 Mbps)\n"
                    . "• Monthly price\n"
                    . "• Bandwidth limit (download/upload speed)\n"
                    . "• MikroTik profile — the profile name used on the router\n\n"
                    . "When a package is created, the system automatically creates a matching profile on the MikroTik router.",
            ],
            'admin.settings.dropdown-options' => [
                'title' => 'Dropdown Options',
                'content' => "**Dropdown Options** — customize dropdown menus used in forms.\n\n"
                    . "Add or remove payment methods, cable types, expense categories, and more.",
            ],
            'admin.config.protocol-type' => [
                'title' => 'Protocol Type',
                'content' => "**Protocol Type** — define connection protocols.\n\n"
                    . "Examples: PPPoE, DHCP, Static IP, etc.",
            ],
            'admin.config.district' => [
                'title' => 'District',
                'content' => "**District** — manage the list of districts. Required for client address fields.",
            ],
            'admin.config.upazila' => [
                'title' => 'Upazila',
                'content' => "**Upazila** — manage upazilas (sub-districts). Set upazilas under each district.",
            ],
        ],
    ],

    'mikrotik' => [
        'title' => 'MikroTik Router',
        'icon' => 'fas fa-ethernet',
        'description' => 'Connect MikroTik routers, manage firewall and NAT rules.',
        'guide' => 'The MikroTik Router module lets you add your routers to the system and manage them directly.',
        'submenus' => [
            'admin.multi-router.index' => [
                'title' => 'Router List',
                'content' => "**Router List** — all MikroTik routers connected to the system.\n\n"
                    . "**To add a router:**\n"
                    . "1. Click the 'Add Router' button\n"
                    . "2. Enter name, IP Address, API Port, Username, and Password\n"
                    . "3. Click 'Test Connection' to verify\n"
                    . "4. Save\n\n"
                    . "Once added, you can create PPPoE users, manage bandwidth, and more directly from the system.",
            ],
            'admin.network.firewall' => [
                'title' => 'Firewall Rules',
                'content' => "**Firewall Rules** — view and manage MikroTik firewall rules.\n\n"
                    . "⚠️ Warning: Firewall changes are applied directly to the router. Incorrect changes may disconnect the network.",
            ],
            'admin.network.nat' => [
                'title' => 'NAT Rules',
                'content' => "**NAT Rules** — manage Network Address Translation rules.\n\n"
                    . "View, add, or modify NAT rules on your router.",
            ],
            'admin.network.bulk-operations' => [
                'title' => 'Bulk Operations',
                'content' => "**Bulk Operations** — perform actions on multiple clients at once.\n\n"
                    . "• Enable/Disable multiple clients at once\n"
                    . "• Change packages in bulk\n"
                    . "• Bulk MikroTik sync — push DB profile to the actual router for all selected clients\n"
                    . "• Change zone, sub zone, or TJ box in bulk\n\n"
                    . "**MikroTik Sync:** When the profile on the router does not match the profile in the software (e.g., after a manual router change), use Sync to push the correct profile from the database to the MikroTik router.",
            ],
            'admin.network.connectivity-test' => [
                'title' => 'Connectivity Test',
                'content' => "**Connectivity Test** — check the connection to your routers.\n\n"
                    . "Verify whether a router is properly connected and responding.",
            ],
        ],
    ],

    'olt' => [
        'title' => 'OLT Management',
        'icon' => 'fas fa-microchip',
        'description' => 'OLT device and ONU management.',
        'guide' => 'The OLT (Optical Line Terminal) module lets you manage your fiber network OLT devices.',
        'submenus' => [
            'admin.olt.index' => [
                'title' => 'OLT Devices',
                'content' => "**OLT Devices** — list of OLTs connected to the system.\n\n"
                    . "Add new OLTs, view the OLT dashboard, and check port usage.",
            ],
            'admin.olt.users' => [
                'title' => 'OLT Users',
                'content' => "**OLT Users** — list of all users registered on the OLT.",
            ],
            'admin.olt.onu-list' => [
                'title' => 'ONU List',
                'content' => "**ONU List** — all ONU (Optical Network Unit) devices.\n\n"
                    . "Check ONU status, signal level, and client mapping.",
            ],
        ],
    ],

    'network' => [
        'title' => 'Network & Devices',
        'icon' => 'fas fa-sitemap',
        'description' => 'Network POP, device diagrams, map, IP pool management.',
        'guide' => 'Manage your entire network infrastructure in the Network module — POPs, devices, fiber, splitters, IP pools, and more.',
        'submenus' => [
            'admin.network.devices' => [
                'title' => 'Network POP',
                'content' => "**Network POP** — Point of Presence management.\n\n"
                    . "A POP is a physical location where your network devices are housed. Add POPs and assign devices to them.",
            ],
            'admin.network.diagram' => [
                'title' => 'Device Diagram',
                'content' => "**Device Diagram** — visual diagram of your network devices.\n\n"
                    . "See graphically which device is connected to which.",
            ],
            'admin.network.client-diagram' => [
                'title' => 'Client Diagram',
                'content' => "**Client Diagram** — visual diagram of client connections.\n\n"
                    . "See which clients are connected to which POP/device.",
            ],
            'admin.network.connections' => [
                'title' => 'Network Connections',
                'content' => "**Network Connections** — manage connections between network devices.\n\n"
                    . "Add device-to-device connections, view port usage, and record cable types.",
            ],
            'admin.network.distributed-items' => [
                'title' => 'Distributed Inv. Items',
                'content' => "**Distributed Inventory Items** — equipment and products deployed in the field.\n\n"
                    . "Track which product has been given to which POP or client.",
            ],
            'admin.network.map' => [
                'title' => 'Map View',
                'content' => "**Map View** — see your network on a map.\n\n"
                    . "View POPs, clients, and fiber lines on a geographic map.",
            ],
            'admin.network.topology' => [
                'title' => 'Network Topology',
                'content' => "**Network Topology** — your complete network topology map.\n\n"
                    . "View the connectivity of all routers, switches, OLTs, and splitters in a topology diagram.",
            ],
            'admin.network.device-logs' => [
                'title' => 'Device Logs',
                'content' => "**Device Logs** — activity logs for network devices.\n\n"
                    . "View device up/down events, configuration changes, and alarms.",
            ],
            'admin.network.splitters' => [
                'title' => 'Splitter Management',
                'content' => "**Splitter Management** — fiber splitter management.\n\n"
                    . "Add splitters, track port usage, and see which client is on which port.",
            ],
            'admin.network.ip-pools' => [
                'title' => 'IP Pool Management',
                'content' => "**IP Pool Management** — manage IP Address pools.\n\n"
                    . "Create IP ranges and track which IP is assigned to which client.",
            ],
            'admin.network.mac-bindings' => [
                'title' => 'MAC Binding',
                'content' => "**MAC Binding** — bind a client's MAC address.\n\n"
                    . "Restrict connections to specific MAC addresses for added security.",
            ],
            'admin.network.radius-settings' => [
                'title' => 'RADIUS Settings',
                'content' => "**RADIUS Settings** — RADIUS server configuration.\n\n"
                    . "Set up a RADIUS server for PPPoE authentication.",
            ],
            'admin.mikrotik-sync.index' => [
                'title' => 'Two-Way Sync',
                'content' => "**Two-Way Sync** — data synchronization between the system and MikroTik routers.\n\n"
                    . "Reconcile PPPoE users on the router with client data in the system.",
            ],
        ],
    ],

    'hrm' => [
        'title' => 'HRM',
        'icon' => 'fas fa-id-card-alt',
        'description' => 'Employee, attendance, salary, leave, and department management.',
        'guide' => 'Manage your employees in the HRM module — attendance, salary, leave, departments, designations, and more.',
        'submenus' => [
            'admin.hrm.employees' => [
                'title' => 'Employees',
                'content' => "**Employees** — all employee records.\n\n"
                    . "**To add an employee:**\n"
                    . "1. Click 'Add Employee'\n"
                    . "2. Enter name, mobile, email, department, and designation\n"
                    . "3. Set basic salary\n"
                    . "4. Enter joining date\n\n"
                    . "**Per employee:**\n"
                    . "• View profile — personal info, attendance history, salary history\n"
                    . "• Edit information\n"
                    . "• Deactivate employee",
            ],
            'admin.hrm.attendance' => [
                'title' => 'Attendance',
                'content' => "**Attendance** — daily attendance management.\n\n"
                    . "**Attendance Steps:**\n"
                    . "1. **Check In** — when the employee arrives\n"
                    . "2. **Lunch Start** — lunch break begins\n"
                    . "3. **Lunch End** — lunch break ends\n"
                    . "4. **Check Out** — when the employee leaves\n\n"
                    . "**Statuses:**\n"
                    . "• Present — arrived on time\n"
                    . "• Late — arrived after the scheduled time\n"
                    . "• Half Day — worked only half the day\n"
                    . "• Absent — did not attend\n"
                    . "• Remote — working from home\n"
                    . "• Leave — on approved leave\n"
                    . "• Holiday — public holiday\n"
                    . "• Weekend — weekly day off\n\n"
                    . "**Work hours:** The system automatically calculates work hours, overtime, and late minutes.",
            ],
            'admin.hrm.salary' => [
                'title' => 'Salary Payroll',
                'content' => "**Salary Payroll** — monthly salary management.\n\n"
                    . "**Processing salaries:**\n"
                    . "1. Select month and year\n"
                    . "2. Click 'Process Salaries'\n"
                    . "3. The system calculates work days and overtime from attendance\n\n"
                    . "**Salary formula:**\n"
                    . "Net Salary = Basic + Allowances + Bonuses - Deductions - Advance Deductions\n\n"
                    . "**Edit each employee's salary:**\n"
                    . "• Add allowances (house rent, medical, transport)\n"
                    . "• Apply deductions (fines, late penalties)\n"
                    . "• Deduct salary advances\n\n"
                    . "**Statuses:**\n"
                    . "• Pending — calculated but not yet paid\n"
                    . "• Partial — partially paid\n"
                    . "• Paid — fully paid",
            ],
            'admin.hrm.departments' => [
                'title' => 'Departments',
                'content' => "**Departments** — office department management.\n\n"
                    . "Create departments (e.g., Admin, Technical, Billing, Support). A department must be selected when adding an employee.",
            ],
            'admin.hrm.designations' => [
                'title' => 'Designations',
                'content' => "**Designations** — employee designation management.\n\n"
                    . "Create designations (e.g., Manager, Technician, Billman, Accountant). Each designation belongs to a department.",
            ],
            'admin.hrm.leave' => [
                'title' => 'Leave Management',
                'content' => "**Leave Management** — employee leave applications and approvals.\n\n"
                    . "**Leave application process:**\n"
                    . "1. Employee submits a leave request\n"
                    . "2. Admin/Manager reviews it\n"
                    . "3. Approve or reject\n"
                    . "4. Approved leaves are reflected in attendance",
            ],
            'admin.permissions.index' => [
                'title' => 'Permission Management',
                'content' => "**Permission Management** — set work permissions for different user roles.\n\n"
                    . "**How it works:**\n"
                    . "• Select a role from the sidebar to configure its permissions\n"
                    . "• Toggle Menu Access — control which menu items the role can see\n"
                    . "• Toggle Page Actions — control what actions the role can perform (e.g., add, edit, delete)\n"
                    . "• User-level overrides — grant or revoke specific permissions for individual users\n\n"
                    . "**Modules covered:** Dashboard, Clients, Billing, MikroTik, OLT, Network, HRM, Support, Events, Tasks, Reports, SMS, Reseller, Accounts, Store, Sales, Inventory, Settings, and more.\n\n"
                    . "**Examples:**\n"
                    . "• Billman — can collect payments only\n"
                    . "• Technician — can view tickets and manage client connections\n"
                    . "• Supervisor — can manage clients, billing, events, and tasks\n"
                    . "• Network Engineer — MikroTik, OLT, and network device access\n\n"
                    . "Super Admin and Administrator roles have full access to all modules.",
            ],
        ],
    ],

    'support' => [
        'title' => 'Support & Ticketing',
        'icon' => 'fas fa-headset',
        'description' => 'Manage client issue tickets.',
        'guide' => 'Track client complaints and issues in the Support module — create tickets, assign technicians, and monitor SLA compliance.',
        'submenus' => [
            'admin.support.tickets' => [
                'title' => 'All Tickets',
                'content' => "**All Tickets** — list of all client support tickets.\n\n"
                    . "**Creating tickets:**\n"
                    . "• Clients can open tickets from the portal\n"
                    . "• Staff can create tickets on behalf of clients\n\n"
                    . "**Ticket workflow:**\n"
                    . "1. New ticket arrives (Open)\n"
                    . "2. Assign a technician\n"
                    . "3. Technician resolves the issue\n"
                    . "4. Close the ticket\n\n"
                    . "**Priority levels:** Low, Medium, High, Critical",
            ],
            'admin.support.categories' => [
                'title' => 'Ticket Categories',
                'content' => "**Ticket Categories** — create categories by issue type.\n\n"
                    . "Examples: Internet Down, Slow Speed, Fiber Cut, Router Issue, etc.",
            ],
            'admin.support.sla-config' => [
                'title' => 'SLA Configuration',
                'content' => "**SLA Configuration** — Service Level Agreement setup.\n\n"
                    . "Define how quickly a ticket must receive a first response and how quickly it must be resolved.",
            ],
            'admin.support.technician-efficiency' => [
                'title' => 'Technician Efficiency',
                'content' => "**Technician Efficiency** — technician performance report.\n\n"
                    . "See how many tickets each technician has resolved and the average resolution time.",
            ],
        ],
    ],

    'tasks' => [
        'title' => 'Task Management',
        'icon' => 'fas fa-clipboard-list',
        'description' => 'Assign and track employee tasks with calendar view.',
        'guide' => 'In the Task module you can assign specific tasks to employees, set deadlines, track progress, and view everything on an interactive calendar.',
        'submenus' => [
            'admin.tasks.index' => [
                'title' => 'All Tasks',
                'content' => "**All Tasks** — list of all created tasks.\n\n"
                    . "**Features:**\n"
                    . "• Create, edit, and delete tasks\n"
                    . "• Assign tasks to any employee\n"
                    . "• Set priority (Low, Medium, High, Urgent)\n"
                    . "• Filter by status, employee, priority, or date range\n"
                    . "• Toggle status between Pending, In Progress, and Completed\n"
                    . "• Pagination for large task lists\n\n"
                    . "**Task statuses:** Pending → In Progress → Completed",
            ],
            'admin.tasks.create' => [
                'title' => 'Create Task',
                'content' => "**Create Task** — assign a new task.\n\n"
                    . "**Required fields:**\n"
                    . "• Task title\n"
                    . "• Description (optional)\n"
                    . "• Assign to an employee\n"
                    . "• Set due date\n"
                    . "• Set priority level (Low, Medium, High, Urgent)\n\n"
                    . "Newly created tasks start in Pending status.",
            ],
            'admin.tasks.calendar' => [
                'title' => 'Task Calendar',
                'content' => "**Task Calendar** — interactive calendar view of all tasks.\n\n"
                    . "**Features:**\n"
                    . "• Monthly, weekly, and list views\n"
                    . "• Tasks are color-coded by priority\n"
                    . "• Click a task to view details\n"
                    . "• Tasks are placed on the calendar based on their due date",
            ],
        ],
    ],

    'store' => [
        'title' => 'Store',
        'icon' => 'fas fa-store',
        'description' => 'Product purchase, sales, and stock management.',
        'guide' => 'In the Store module, manage products used in your ISP business (routers, cables, ONUs, etc.) — purchasing, stock, and sales.',
        'submenus' => [
            'admin.store.categories' => [
                'title' => 'Product Categories',
                'content' => "**Product Categories** — define product types.\n\n"
                    . "Examples: Router, ONU, Fiber Cable, Connector, Splitter, etc.",
            ],
            'admin.store.products' => [
                'title' => 'Products',
                'content' => "**Products** — product list and stock.\n\n"
                    . "Add products — name, category, purchase price, selling price, stock quantity.",
            ],
            'admin.store.purchases' => [
                'title' => 'Purchase List',
                'content' => "**Purchase List** — all purchase records.\n\n"
                    . "Track which product was bought from which vendor at what price.",
            ],
            'admin.purchase.create' => [
                'title' => 'Add Purchase',
                'content' => "**Add Purchase** — purchase products.\n\n"
                    . "Select a vendor, add products and quantities, set the price, and save. Stock is updated automatically.",
            ],
            'admin.purchase.vendors' => [
                'title' => 'Vendors',
                'content' => "**Vendors** — supplier management.\n\n"
                    . "Keep records of your suppliers — name, phone, address.",
            ],
        ],
    ],

    'sales' => [
        'title' => 'Sales & Service',
        'icon' => 'fas fa-shopping-cart',
        'description' => 'Product sales, service charges, installation charges.',
        'guide' => 'In the Sales module, manage product sales to clients (routers, ONUs, etc.) and track service/installation charges.',
        'submenus' => [
            'admin.sales.index' => [
                'title' => 'Sales List',
                'content' => "**Sales List** — all sales records.\n\nView invoice number, client, product, quantity, and total amount.",
            ],
            'admin.sales.create' => [
                'title' => 'New Sale',
                'content' => "**New Sale** — sell products to a client.\n\n"
                    . "Select a client, add products, set prices, and create an invoice.",
            ],
            'admin.sales.services' => [
                'title' => 'Service Charges',
                'content' => "**Service Charges** — charges for repairs or services.\n\n"
                    . "Record charges when a service is provided to a client (e.g., line repair, router configuration).",
            ],
            'admin.sales.installation-charges' => [
                'title' => 'Installation Charges',
                'content' => "**Installation Charges** — new connection installation fees.\n\n"
                    . "Record installation fees charged when setting up a new client connection.",
            ],
        ],
    ],

    'inventory' => [
        'title' => 'Inventory',
        'icon' => 'fas fa-warehouse',
        'description' => 'Product stock, in/out tracking, fiber, and instrument tracking.',
        'guide' => 'Track all your products and equipment in the Inventory module — stock levels, serial numbers, and fiber usage.',
        'submenus' => [
            'admin.inventory.stock.summary' => [
                'title' => 'Dashboard',
                'content' => "**Inventory Dashboard** — overall stock overview.\n\n"
                    . "Total products, current stock levels, and low-stock alerts — all at a glance.",
            ],
            'admin.inventory.vendors.index' => [
                'title' => 'Vendors',
                'content' => "**Vendors** — list of inventory suppliers.\n\n"
                    . "Keep supplier details — name, phone, address, and transaction history.",
            ],
            'admin.inventory.instrument-categories.page' => [
                'title' => 'Categories',
                'content' => "**Categories** — inventory product category management.\n\n"
                    . "Create categories by product type (e.g., Router, ONU, Cable, Connector, etc.).",
            ],
            'admin.inventory.stock.availability' => [
                'title' => 'Stock Availability',
                'content' => "**Stock Availability** — current stock status of all products.\n\n"
                    . "See what's in stock, what's running low, and what's out.\n\n"
                    . "• Low-stock alerts\n"
                    . "• View reorder levels",
            ],
            'admin.inventory.products.index' => [
                'title' => 'Products Stocks',
                'content' => "**Products Stocks** — all inventory products and current stock.\n\n"
                    . "Add products, update stock, and track serial numbers.",
            ],
            'admin.inventory.products.in_history' => [
                'title' => 'Product In History',
                'content' => "**Product In History** — history of products received into stock.\n\n"
                    . "See when and how many of each product were added to inventory.",
            ],
            'admin.inventory.products.out' => [
                'title' => 'Product Out',
                'content' => "**Product Out** — remove products from stock.\n\n"
                    . "Record when products are given to clients, sent to the field, or removed for any reason.",
            ],
            'admin.inventory.products.out_history' => [
                'title' => 'Product Out History',
                'content' => "**Product Out History** — history of products removed from stock.\n\n"
                    . "Full record of when each product was given out and to whom.",
            ],
            'admin.inventory.fiber.analytics' => [
                'title' => 'Fiber Analytics',
                'content' => "**Fiber Analytics** — fiber usage analysis.\n\n"
                    . "• Total fiber purchased vs. used\n"
                    . "• Fiber usage by area/zone\n"
                    . "• Monthly usage trends",
            ],
            'admin.inventory.serial.search' => [
                'title' => 'Serial Search',
                'content' => "**Serial Search** — find a product by serial number.\n\n"
                    . "Enter an ONU or router serial number to find which client it was assigned to and where it is.",
            ],
            'admin.inventory.fiber.index' => [
                'title' => 'Fiber Stock',
                'content' => "**Fiber Stock** — fiber cable stock management.\n\n"
                    . "Track how many meters of fiber were purchased, used, and remaining.",
            ],
            'admin.inventory.instruments.index' => [
                'title' => 'Instruments',
                'content' => "**Instruments** — technician equipment tracking.\n\n"
                    . "Track which instrument is with which technician — OTDR, power meter, etc.",
            ],
            'admin.inventory.damage.index' => [
                'title' => 'Damaged Items',
                'content' => "**Damaged Items** — record of damaged or broken products.",
            ],
        ],
    ],

    'accounts' => [
        'title' => 'Accounts',
        'icon' => 'fas fa-balance-scale',
        'description' => 'Income, expense, bank account, and loan management.',
        'guide' => 'Keep your financial records in the Accounts module — cash, income, expenses, bank accounts, and loans.',
        'submenus' => [
            'admin.accounts.cash' => [
                'title' => 'Cash In Hand',
                'content' => "**Cash In Hand** — track cash available at the office.\n\n"
                    . "Monitor how much cash is on hand, when money came in, and when it went out.",
            ],
            'admin.accounts.income' => [
                'title' => 'Income',
                'content' => "**Income** — all income records.\n\n"
                    . "Bill collections, installation charges, sales — view all income and add new entries.",
            ],
            'admin.accounts.expense' => [
                'title' => 'Expense',
                'content' => "**Expense** — all expense records.\n\n"
                    . "Office rent, electricity, salaries, transport, repairs — record all expenses here.\n\n"
                    . "• View expenses by category\n"
                    . "• Compare monthly expenses",
            ],
            'admin.accounts.banks' => [
                'title' => 'Bank Accounts',
                'content' => "**Bank Accounts** — bank account management.\n\n"
                    . "Add bank accounts, record transactions, and track balances.",
            ],
            'admin.accounts.loan' => [
                'title' => 'Loan Management',
                'content' => "**Loan Management** — manage given or received loans.\n\n"
                    . "Track loan amounts, installments, due dates, and remaining balance.",
            ],
        ],
    ],

    'sms' => [
        'title' => 'SMS Service',
        'icon' => 'fas fa-paper-plane',
        'description' => 'Send SMS to clients and configure settings.',
        'guide' => 'Use the SMS module to send bill reminders, notifications, and promotional SMS to clients.',
        'submenus' => [
            'admin.sms.individual' => [
                'title' => 'Individual SMS',
                'content' => "**Individual SMS** — send an SMS to a specific number.\n\n"
                    . "Enter the number, type your message, and send.",
            ],
            'admin.sms.bulk' => [
                'title' => 'Send SMS',
                'content' => "**Send SMS** — send bulk SMS to many clients at once.\n\n"
                    . "• All clients\n"
                    . "• Clients in a specific zone\n"
                    . "• Clients with dues\n"
                    . "• Clients on a specific package",
            ],
            'admin.sms.templates' => [
                'title' => 'SMS Templates',
                'content' => "**SMS Templates** — create pre-defined SMS templates.\n\n"
                    . "Create templates for bill reminders, payment confirmations, welcome messages, etc.",
            ],
            'admin.sms.logs' => [
                'title' => 'SMS Logs',
                'content' => "**SMS Logs** — record of all sent SMS messages.\n\n"
                    . "See which number received which message, when, and whether it was delivered successfully.",
            ],
            'admin.sms.gateway' => [
                'title' => 'SMS Gateway',
                'content' => "**SMS Gateway** — set up the SMS sending gateway.\n\n"
                    . "Enter your SMS provider's API credentials (API Key, Sender ID, etc.).",
            ],
            'admin.settings.sms' => [
                'title' => 'SMS Settings',
                'content' => "**SMS Settings** — automatic SMS configuration.\n\n"
                    . "Choose which events trigger automatic SMS — bill generation, payment, connection disable, etc.",
            ],
        ],
    ],

    'reseller' => [
        'title' => 'MAC Reseller',
        'icon' => 'fas fa-handshake',
        'description' => 'Reseller management, clients, recharge, and billing.',
        'guide' => 'The Reseller module lets you manage MAC resellers who sell your services under their own brand. Resellers can recharge their clients, and the system automatically manages MikroTik profiles based on recharge status.',
        'submenus' => [
            'admin.reseller.index' => [
                'title' => 'MAC Reseller List',
                'content' => "**MAC Reseller List** — all registered resellers.\n\n"
                    . "Add new resellers, set their packages and pricing, and manage their accounts.\n\n"
                    . "**Reseller types:**\n"
                    . "• MAC Reseller — per-client recharge billing\n"
                    . "• Bandwidth Reseller — monthly bandwidth billing\n\n"
                    . "**Billing types:** Prepaid (wallet) or Postpaid (credit limit)\n"
                    . "**Recharge types:** Single day, Multi-day, or Flexible day",
            ],
            'admin.reseller.clients' => [
                'title' => 'Reseller Clients',
                'content' => "**Reseller Clients** — view clients under each reseller.\n\n"
                    . "**Recharge & MikroTik auto-management:**\n"
                    . "• When a reseller recharges a client, an Expire Date is set\n"
                    . "• If the client's MikroTik profile was Inactive, it is automatically restored to the original profile (both in software and on the MikroTik router)\n"
                    . "• When the Expire Date passes, the system auto-disables the client and sets the MikroTik profile to Inactive\n"
                    . "• Reseller clients are disabled based on Expire Date only — not based on billing date or payment deadline\n\n"
                    . "**Expire Date:** Can be set or cleared manually from the client edit page. Leave empty for no auto-expiry.",
            ],
            'admin.reseller.funding' => [
                'title' => 'Reseller Billing',
                'content' => "**Reseller Billing** — collect payments from resellers and manage their balance.\n\n"
                    . "Add funds, view billing history, and track balances.\n\n"
                    . "**For MAC resellers:** Monthly bill = total recharge amount for the month\n"
                    . "**For Bandwidth resellers:** Monthly bill = bandwidth Mbps × rate per Mbps",
            ],
        ],
    ],

    'agent' => [
        'title' => 'Referral Partner',
        'icon' => 'fas fa-user-tie',
        'description' => 'Add referral partners and manage commissions.',
        'guide' => 'Referral partners bring in new clients and earn commissions in return.',
        'submenus' => [
            'admin.agent.create' => [
                'title' => 'Add Referral Partner',
                'content' => "**Add Referral Partner** — register a new referral partner.\n\n"
                    . "Enter name, phone, and commission rate.",
            ],
            'admin.agent.index' => [
                'title' => 'Referral Partner List',
                'content' => "**Referral Partner List** — all referral partners and their performance.",
            ],
            'admin.agent.ledgers' => [
                'title' => 'Referral Partner Ledger',
                'content' => "**Referral Partner Ledger** — commission records.\n\n"
                    . "See how much commission was earned, paid, and how much is pending.",
            ],
        ],
    ],

    'events' => [
        'title' => 'Events & Holidays',
        'icon' => 'fas fa-glass-cheers',
        'description' => 'Events, holidays, and office calendar with full CRUD.',
        'guide' => 'Manage office events and public holidays. Holidays set here are automatically reflected in HRM attendance. Full create, edit, and delete support.',
        'submenus' => [
            'admin.events.index' => [
                'title' => 'Events List',
                'content' => "**Events** — office event management.\n\n"
                    . "**Features:**\n"
                    . "• Create events for meetings, maintenance, celebrations, etc.\n"
                    . "• Set event title, description, date, and type\n"
                    . "• Filter events by type or date range\n"
                    . "• Edit or delete existing events\n\n"
                    . "**Event types:** Holiday, Event, Maintenance, Other",
            ],
            'admin.events.holidays' => [
                'title' => 'Holidays',
                'content' => "**Holidays** — public and company holiday list.\n\n"
                    . "Adding a holiday automatically marks that day as 'Holiday' in attendance records.\n\n"
                    . "**Features:**\n"
                    . "• Add and delete holidays\n"
                    . "• Set holiday name and date\n"
                    . "• Holidays are reflected system-wide in HRM attendance",
            ],
        ],
    ],

    'data_transfer' => [
        'title' => 'Data Transfer',
        'icon' => 'fas fa-cloud-upload-alt',
        'description' => 'Data import, export, and migration.',
        'guide' => 'Use the Data Transfer module to import data from other systems or export data from this system.',
        'submenus' => [
            'admin.migration.index' => [
                'title' => 'Migration Wizard',
                'content' => "**Migration Wizard** — migrate data from another ISP software.\n\n"
                    . "Follow step-by-step instructions to bring all clients, bills, and payment data from your old system.",
            ],
            'admin.data-transfer.quick' => [
                'title' => 'Quick Import/Export',
                'content' => "**Quick Import/Export** — quickly import or export data using Excel/CSV files.",
            ],
            'admin.data-transfer.index' => [
                'title' => 'Overview',
                'content' => "**Overview** — data transfer summary.\n\n"
                    . "See all import and export options in one place, and review recent activity.",
            ],
            'admin.data-transfer.import.index' => [
                'title' => 'Import Data',
                'content' => "**Import Data** — import different types of data.\n\n"
                    . "Import clients, bills, payments, etc. from CSV/Excel files.",
            ],
            'admin.data-transfer.export.index' => [
                'title' => 'Export Data',
                'content' => "**Export Data** — download system data as Excel/CSV.",
            ],
        ],
    ],

    'settings' => [
        'title' => 'Settings',
        'icon' => 'fas fa-wrench',
        'description' => 'Company profile, system settings, logo, payment methods.',
        'guide' => 'Configure your company\'s basic information, system settings, logo, and more in the Settings module.',
        'submenus' => [
            'admin.settings.company' => [
                'title' => 'Company Profile',
                'content' => "**Company Profile** — your ISP company details.\n\n"
                    . "Set company name, address, phone, email, website, and license number. This information is used on bills, invoices, and reports.",
            ],
            'admin.settings.payment-methods' => [
                'title' => 'Payment Methods',
                'content' => "**Payment Methods** — set up payment acceptance channels.\n\n"
                    . "Add: Cash, bKash, Nagad (MFS), Rocket, Bank Transfer, etc.",
            ],
            'admin.settings.system' => [
                'title' => 'System Settings',
                'content' => "**System Settings** — general application settings.\n\n"
                    . "Configure timezone, currency, date format, language, and more.",
            ],
            'admin.settings.logo' => [
                'title' => 'Logo Settings',
                'content' => "**Logo Settings** — upload your company logo.\n\n"
                    . "This logo appears on the sidebar, login page, bills, invoices, and reports.",
            ],
            'admin.settings.payment' => [
                'title' => 'Payment Settings',
                'content' => "**Payment Settings** — online payment gateway configuration.\n\n"
                    . "Set up bKash, Nagad, SSLCommerz, and other online payment gateways.",
            ],
            'admin.system.logs' => [
                'title' => 'System Logs',
                'content' => "**System Logs** — log of all system activities.\n\n"
                    . "Who did what and when — login, client creation, payment, settings changes, etc.",
            ],
        ],
    ],

    'whatsapp' => [
        'title' => 'WhatsApp',
        'icon' => 'fab fa-whatsapp',
        'description' => 'Send WhatsApp messages and configure settings.',
        'guide' => 'Use the WhatsApp module to send bill reminders and notifications to clients via WhatsApp.',
        'submenus' => [
            'admin.whatsapp.settings' => [
                'title' => 'Settings',
                'content' => "**WhatsApp Settings** — WhatsApp Business API configuration.",
            ],
            'admin.whatsapp.templates' => [
                'title' => 'Templates',
                'content' => "**WhatsApp Templates** — create and manage WhatsApp message templates.",
            ],
            'admin.whatsapp.logs' => [
                'title' => 'Message Logs',
                'content' => "**Message Logs** — record of all sent WhatsApp messages.",
            ],
        ],
    ],

    'branches' => [
        'title' => 'Branch & POP',
        'icon' => 'fas fa-building',
        'description' => 'Branch office and POP management.',
        'guide' => 'If you have multiple branch offices, manage branches and POPs here.',
        'submenus' => [
            'admin.branches.index' => [
                'title' => 'Branches',
                'content' => "**Branches** — list of your ISP branch offices.\n\nAdd new branches and organize clients and staff by branch.",
            ],
            'admin.pops.index' => [
                'title' => 'POPs',
                'content' => "**POPs** — Point of Presence list.\n\nManage the physical locations of your network devices.",
            ],
        ],
    ],

    'security' => [
        'title' => 'Security',
        'icon' => 'fas fa-fingerprint',
        'description' => 'Two-factor auth, password policy, login logs, API tokens.',
        'guide' => 'Manage your system\'s security settings in the Security module.',
        'submenus' => [
            'admin.security.index' => [
                'title' => 'Security Dashboard',
                'content' => "**Security Dashboard** — overview of your system's security posture.",
            ],
            'admin.2fa.setup' => [
                'title' => 'Two-Factor Authentication',
                'content' => "**2FA** — enable two-step verification for added security.\n\n"
                    . "Use Google Authenticator or a similar app to require an additional code at login.",
            ],
            'admin.security.password-policy' => [
                'title' => 'Password Policy',
                'content' => "**Password Policy** — set minimum password requirements.\n\n"
                    . "Minimum length, uppercase letters, numbers, special characters, etc.",
            ],
            'admin.security.login-logs' => [
                'title' => 'Login Logs',
                'content' => "**Login Logs** — record of all login attempts.\n\n"
                    . "See who logged in, when, from where, and how many failed attempts occurred.",
            ],
            'admin.security.api-tokens' => [
                'title' => 'API Tokens',
                'content' => "**API Tokens** — create and manage API tokens for external system integration.",
            ],
        ],
    ],

    'card_billing' => [
        'title' => 'Card Billing',
        'icon' => 'fas fa-credit-card',
        'description' => 'Create, distribute, and redeem prepaid cards.',
        'guide' => 'The Card Billing module lets you create and distribute prepaid recharge cards.',
        'submenus' => [
            'admin.card-billing.batches' => [
                'title' => 'Card Batches',
                'content' => "**Card Batches** — create batches of cards.\n\n"
                    . "Each batch generates multiple cards — set the quantity and denomination.",
            ],
            'admin.card-billing.cards' => [
                'title' => 'All Cards',
                'content' => "**All Cards** — list of all created cards and their statuses.",
            ],
            'admin.card-billing.redeem' => [
                'title' => 'Redeem Card',
                'content' => "**Redeem Card** — use a card code to recharge a client's balance.",
            ],
            'admin.card-billing.distributors' => [
                'title' => 'Distributors',
                'content' => "**Distributors** — manage card distributors.",
            ],
        ],
    ],

    'vpn' => [
        'title' => 'VPN & Forwarding',
        'icon' => 'fas fa-lock',
        'description' => 'VPN accounts and port forwarding.',
        'guide' => 'Manage VPN services and port forwarding for clients in the VPN module.',
        'submenus' => [
            'admin.vpn.index' => [
                'title' => 'VPN Accounts',
                'content' => "**VPN Accounts** — list and manage VPN users.",
            ],
            'admin.port-forward.index' => [
                'title' => 'Port Forwarding',
                'content' => "**Port Forwarding** — set up port forwarding rules for clients.\n\n"
                    . "Forward ports for CCTV, IP cameras, servers, and more.",
            ],
        ],
    ],

    'ipam' => [
        'title' => 'IPAM',
        'icon' => 'fas fa-globe',
        'description' => 'IP Address Management — create and manage IP pools.',
        'guide' => 'Centrally manage your IP address resources in the IPAM module.',
        'submenus' => [
            'admin.ipam.index' => [
                'title' => 'IP Pools',
                'content' => "**IP Pools** — list of all created IP pools.\n\nSee how many IPs are in each pool, how many are used, and how many are available.",
            ],
            'admin.ipam.create' => [
                'title' => 'Create Pool',
                'content' => "**Create Pool** — create a new IP range/subnet pool.",
            ],
        ],
    ],

    'minute_sales' => [
        'title' => 'Minute Sales',
        'icon' => 'fas fa-history',
        'description' => 'Time-based internet package sales.',
        'guide' => 'Sell time-based (hourly/daily) internet packages with the Minute Sales module.',
        'submenus' => [
            'admin.minute-sales.packages' => [
                'title' => 'Packages',
                'content' => "**Minute Packages** — create time-based packages (e.g., 1 hour for 50 BDT, 1 day for 100 BDT).",
            ],
            'admin.minute-sales.sell' => [
                'title' => 'Sell Minutes',
                'content' => "**Sell Minutes** — activate a time-based package for a client.",
            ],
            'admin.minute-sales.sales' => [
                'title' => 'Sales Report',
                'content' => "**Sales Report** — detailed minute sales report.",
            ],
        ],
    ],

    'daily_billing' => [
        'title' => 'Daily Billing',
        'icon' => 'fas fa-calendar-day',
        'description' => 'Manage billing on a daily basis.',
        'guide' => 'Use the Daily Billing module for per-day billing instead of monthly billing.',
        'submenus' => [],
    ],

    'hotspot' => [
        'title' => 'Hotspot Editor',
        'icon' => 'fas fa-wifi',
        'description' => 'Customize the MikroTik hotspot login page.',
        'guide' => 'Design and customize your MikroTik router\'s hotspot login page with the Hotspot Editor.',
        'submenus' => [],
    ],

    'backups' => [
        'title' => 'Backup & Restore',
        'icon' => 'fas fa-hdd',
        'description' => 'Database backup and restore.',
        'guide' => 'Take regular database backups and restore when needed.',
        'submenus' => [],
    ],

    'payment_links' => [
        'title' => 'Payment Links',
        'icon' => 'fas fa-money-bill-wave',
        'description' => 'Create payment links for clients.',
        'guide' => 'Create payment links and share them with clients — they can click the link to pay online.',
        'submenus' => [],
    ],

    'voice_calls' => [
        'title' => 'Voice Calls',
        'icon' => 'fas fa-phone-alt',
        'description' => 'Call history and voice call settings.',
        'guide' => 'Send automated voice calls to clients through the Voice Calls module.',
        'submenus' => [
            'admin.voice-calls.index' => [
                'title' => 'Call History',
                'content' => "**Call History** — record of all voice calls sent.",
            ],
            'admin.voice-calls.settings' => [
                'title' => 'Settings',
                'content' => "**Voice Call Settings** — voice call service configuration.",
            ],
        ],
    ],
];
