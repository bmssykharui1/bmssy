package com.codevern.bmssykharuii.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import com.codevern.bmssykharuii.data.SessionManager
import com.codevern.bmssykharuii.data.dataStore

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(agentId: String) {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()
    val sessionManager = remember { SessionManager(context) }
    
    val agentName by sessionManager.loggedInAgentId.collectAsState(initial = "Loading...") // Fallback, actually we need name
    // Since SessionManager currently only exposes loggedInAgentId flow easily, we can add flows for name and area.
    // For now, let's collect them directly from DataStore
    val name by context.dataStore.data.collectAsState(initial = null)
    val actualAgentName = name?.get(SessionManager.AGENT_NAME) ?: "Agent"
    val actualAgentArea = name?.get(SessionManager.AGENT_AREA) ?: "Unknown Area"

    val drawerState = rememberDrawerState(initialValue = DrawerValue.Closed)
    var selectedItem by remember { mutableStateOf("Dashboard") }

    // Accordion state
    var expandedMenu by remember { mutableStateOf<String?>(null) }

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            ModalDrawerSheet(
                drawerContainerColor = Color.White,
                modifier = Modifier.width(280.dp)
            ) {
                Column(
                    modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())
                ) {
                    // Sidebar Header (Blue Background)
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFF0284C7))
                            .padding(horizontal = 24.dp, vertical = 32.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(48.dp)
                                    .clip(RoundedCornerShape(12.dp))
                                    .background(Color.White),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(Icons.Default.Shield, contentDescription = null, tint = Color(0xFF0284C7), modifier = Modifier.size(28.dp))
                            }
                            Spacer(modifier = Modifier.width(16.dp))
                            Text(text = "BMSSY KHARUI I", fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = Color.White)
                        }
                    }
                    
                    Spacer(modifier = Modifier.height(8.dp))

                    // Dashboard
                    DrawerMenuItem(icon = Icons.Default.Home, label = "Dashboard", iconColor = Color(0xFF0B57D0), isSelected = selectedItem == "Dashboard") {
                        selectedItem = "Dashboard"
                        coroutineScope.launch { drawerState.close() }
                    }

                    // Add New
                    DrawerMenuItem(icon = Icons.Default.AddBox, label = "Add New", iconColor = Color(0xFF146C2E), isSelected = selectedItem == "Add New", badge = "New") {
                        selectedItem = "Add New"
                        coroutineScope.launch { drawerState.close() }
                    }

                    // PF Updation
                    DrawerMenuDropdown(
                        icon = Icons.Default.CheckCircle, label = "PF Updation", iconColor = Color(0xFF6750A4), 
                        isOpen = expandedMenu == "PF", onClick = { expandedMenu = if (expandedMenu == "PF") null else "PF" }
                    )
                    androidx.compose.animation.AnimatedVisibility(
                        visible = expandedMenu == "PF",
                        enter = androidx.compose.animation.expandVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow)),
                        exit = androidx.compose.animation.shrinkVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow))
                    ) {
                        Column {
                            DrawerSubMenuItem("Others", selectedItem == "PF Updation - Others") { selectedItem = "PF Updation - Others"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("Contractions", selectedItem == "PF Updation - Contractions") { selectedItem = "PF Updation - Contractions"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("Settings", selectedItem == "PF Updation - Settings") { selectedItem = "PF Updation - Settings"; coroutineScope.launch { drawerState.close() } }
                        }
                    }

                    // Duare Sorkar
                    DrawerMenuDropdown(
                        icon = Icons.Default.People, label = "Duare Sorkar", iconColor = Color(0xFFB36B00), 
                        isOpen = expandedMenu == "DS", onClick = { expandedMenu = if (expandedMenu == "DS") null else "DS" }
                    )
                    androidx.compose.animation.AnimatedVisibility(
                        visible = expandedMenu == "DS",
                        enter = androidx.compose.animation.expandVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow)),
                        exit = androidx.compose.animation.shrinkVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow))
                    ) {
                        Column {
                            DrawerSubMenuItem("Entry", selectedItem == "Duare Sorkar - Entry") { selectedItem = "Duare Sorkar - Entry"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("PF Update", selectedItem == "Duare Sorkar - PF Update") { selectedItem = "Duare Sorkar - PF Update"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("DS List", selectedItem == "Duare Sorkar - DS List") { selectedItem = "Duare Sorkar - DS List"; coroutineScope.launch { drawerState.close() } }
                        }
                    }

                    // Lists
                    DrawerMenuDropdown(
                        icon = Icons.Default.List, label = "Lists", iconColor = Color(0xFFB3261E), 
                        isOpen = expandedMenu == "Lists", onClick = { expandedMenu = if (expandedMenu == "Lists") null else "Lists" }
                    )
                    androidx.compose.animation.AnimatedVisibility(
                        visible = expandedMenu == "Lists",
                        enter = androidx.compose.animation.expandVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow)),
                        exit = androidx.compose.animation.shrinkVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow))
                    ) {
                        Column {
                            DrawerSubMenuItem("All Data", selectedItem == "Lists - All Data") { selectedItem = "Lists - All Data"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("PF Update", selectedItem == "Lists - PF Update") { selectedItem = "Lists - PF Update"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("New Data", selectedItem == "Lists - New Data") { selectedItem = "Lists - New Data"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("Inactive Data", selectedItem == "Lists - Inactive Data") { selectedItem = "Lists - Inactive Data"; coroutineScope.launch { drawerState.close() } }
                        }
                    }

                    // Form 4
                    DrawerMenuDropdown(
                        icon = Icons.Default.Description, label = "Form 4", iconColor = Color(0xFF0D9488), 
                        isOpen = expandedMenu == "Form4", onClick = { expandedMenu = if (expandedMenu == "Form4") null else "Form4" }
                    )
                    androidx.compose.animation.AnimatedVisibility(
                        visible = expandedMenu == "Form4",
                        enter = androidx.compose.animation.expandVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow)),
                        exit = androidx.compose.animation.shrinkVertically(animationSpec = androidx.compose.animation.core.spring(stiffness = androidx.compose.animation.core.Spring.StiffnessLow))
                    ) {
                        Column {
                            DrawerSubMenuItem("Add New", selectedItem == "Form 4 - Add New") { selectedItem = "Form 4 - Add New"; coroutineScope.launch { drawerState.close() } }
                            DrawerSubMenuItem("Download PDF", selectedItem == "Form 4 - Download PDF") { selectedItem = "Form 4 - Download PDF"; coroutineScope.launch { drawerState.close() } }
                        }
                    }

                    Spacer(modifier = Modifier.height(32.dp))
                    HorizontalDivider(color = Color(0xFFF1F5F9))
                    
                    // Agent Footer Info
                    Column(modifier = Modifier.padding(24.dp)) {
                        Text(text = actualAgentName, fontWeight = FontWeight.Bold, fontSize = 16.sp, color = Color(0xFF0F172A))
                        Text(text = "ID: $agentId | $actualAgentArea", fontSize = 14.sp, color = Color(0xFF64748B))
                        
                        Spacer(modifier = Modifier.height(16.dp))
                        
                        // Logout Button
                        Button(
                            onClick = { coroutineScope.launch { sessionManager.clearSession() } },
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFEF2F2), contentColor = Color(0xFFDC2626)),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            elevation = ButtonDefaults.buttonElevation(0.dp)
                        ) {
                            Icon(Icons.Default.ExitToApp, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Logout", fontWeight = FontWeight.Bold)
                        }
                    }
                    Spacer(modifier = Modifier.height(24.dp))
                }
            }
        }
    ) {
        Scaffold(
            topBar = {
                TopAppBar(
                    title = { Text(text = selectedItem, fontWeight = FontWeight.Bold) },
                    navigationIcon = {
                        IconButton(onClick = { coroutineScope.launch { drawerState.open() } }) {
                            Icon(Icons.Default.Menu, contentDescription = "Menu")
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = Color(0xFF0284C7),
                        titleContentColor = Color.White,
                        navigationIconContentColor = Color.White
                    )
                )
            },
            containerColor = Color(0xFFF8FAFC)
        ) { paddingValues ->
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                when (selectedItem) {
                    "Dashboard" -> DashboardOverviewScreen(agentId = agentId)
                    "Add New" -> AddNewBeneficiaryScreen()
                    
                    "PF Updation - Others" -> PfUpdationOthersScreen()
                    "PF Updation - Contractions" -> PfUpdationContractionsScreen()
                    "PF Updation - Settings" -> PfUpdationSettingsScreen()

                    "Duare Sorkar - Entry" -> DuareSorkarEntryScreen()
                    "Duare Sorkar - PF Update" -> DuareSorkarPfUpdateScreen()
                    "Duare Sorkar - DS List" -> DuareSorkarListScreen()

                    "Lists - All Data" -> AllDataListScreen()
                    "Lists - New Data" -> NewDataListScreen()
                    "Lists - Inactive Data" -> InactiveDataListScreen()
                    "Lists - PF Update" -> PfUpdateListScreen()

                    "Form 4 - Add New" -> Form4AddNewScreen()
                    "Form 4 - Download PDF" -> Form4DownloadPdfScreen()
                    else -> {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(
                                text = "Content for $selectedItem",
                                textAlign = TextAlign.Center,
                                color = Color(0xFF64748B),
                                fontSize = 20.sp,
                                fontWeight = FontWeight.Bold
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                text = "Welcome $actualAgentName ($agentId) from $actualAgentArea",
                                textAlign = TextAlign.Center,
                                color = Color(0xFF94A3B8),
                                fontSize = 14.sp
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun DrawerMenuItem(
    icon: ImageVector,
    label: String,
    iconColor: Color,
    isSelected: Boolean,
    badge: String? = null,
    onClick: () -> Unit
) {
    val backgroundColor = if (isSelected) Color(0xFFF1F5F9) else Color.Transparent

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 2.dp)
            .clip(RoundedCornerShape(8.dp))
            .background(backgroundColor)
            .clickable { onClick() }
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(imageVector = icon, contentDescription = label, tint = iconColor, modifier = Modifier.size(22.dp))
        Spacer(modifier = Modifier.width(16.dp))
        Text(text = label, color = Color(0xFF334155), fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium, fontSize = 15.sp, modifier = Modifier.weight(1f))
        
        if (badge != null) {
            Box(
                modifier = Modifier.clip(RoundedCornerShape(4.dp)).background(Color(0xFFDC2626)).padding(horizontal = 6.dp, vertical = 2.dp)
            ) {
                Text(text = badge, color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}

@Composable
fun DrawerMenuDropdown(
    icon: ImageVector,
    label: String,
    iconColor: Color,
    isOpen: Boolean,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 2.dp)
            .clip(RoundedCornerShape(8.dp))
            .clickable { onClick() }
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(imageVector = icon, contentDescription = label, tint = iconColor, modifier = Modifier.size(22.dp))
        Spacer(modifier = Modifier.width(16.dp))
        Text(text = label, color = Color(0xFF334155), fontWeight = FontWeight.Medium, fontSize = 15.sp, modifier = Modifier.weight(1f))
        Icon(
            imageVector = if (isOpen) Icons.Default.KeyboardArrowDown else Icons.Default.KeyboardArrowRight, 
            contentDescription = null, 
            tint = Color(0xFF94A3B8),
            modifier = Modifier.size(20.dp)
        )
    }
}

@Composable
fun DrawerSubMenuItem(
    label: String,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    val backgroundColor = if (isSelected) Color(0xFFF1F5F9) else Color.Transparent
    val textColor = if (isSelected) Color(0xFF0F172A) else Color(0xFF64748B)
    
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 1.dp)
            .clip(RoundedCornerShape(8.dp))
            .background(backgroundColor)
            .clickable { onClick() }
            .padding(start = 54.dp, top = 10.dp, bottom = 10.dp, end = 16.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(text = label, color = textColor, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium, fontSize = 14.sp)
    }
}
