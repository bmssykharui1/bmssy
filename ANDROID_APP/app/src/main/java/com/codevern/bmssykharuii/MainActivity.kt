package com.codevern.bmssykharuii

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import com.codevern.bmssykharuii.network.AppUpdate
import com.codevern.bmssykharuii.data.SessionManager
import com.codevern.bmssykharuii.network.UpdateManager
import com.codevern.bmssykharuii.ui.screens.LoginScreen
import com.codevern.bmssykharuii.ui.screens.UpdateDialog
import com.codevern.bmssykharuii.ui.theme.BMSSYKHARUIITheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        
        val sessionManager = SessionManager(this)

        setContent {
            val themeMode by sessionManager.themeMode.collectAsState(initial = "System Default")
            val isSystemDark = isSystemInDarkTheme()
            val darkTheme = when (themeMode) {
                "Light" -> false
                "Dark" -> true
                else -> isSystemDark
            }

            BMSSYKHARUIITheme(darkTheme = darkTheme) {
                val agentId by sessionManager.loggedInAgentId.collectAsState(initial = "LOADING")
                
                var showUpdateDialog by remember { mutableStateOf<AppUpdate?>(null) }
                val updateManager = remember { UpdateManager(this@MainActivity) }

                LaunchedEffect(Unit) {
                    val update = updateManager.checkForUpdates()
                    if (update != null && update.version_code > BuildConfig.VERSION_CODE) {
                        showUpdateDialog = update
                    }
                }

                showUpdateDialog?.let { update ->
                    UpdateDialog(
                        update = update,
                        onDownload = {
                            updateManager.downloadAndInstall(update)
                        },
                        onDismiss = {
                            showUpdateDialog = null
                        }
                    )
                }

                when (agentId) {
                    "LOADING" -> {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator()
                        }
                    }
                    null -> {
                        LoginScreen(
                            onLoginSuccess = {
                                // Re-composition will happen automatically
                            }
                        )
                    }
                    else -> {
                        // Show Dashboard
                        com.codevern.bmssykharuii.ui.screens.DashboardScreen(agentId = agentId!!)
                    }
                }
            }
        }
    }
}