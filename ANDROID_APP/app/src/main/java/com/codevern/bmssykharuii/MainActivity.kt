package com.codevern.bmssykharuii

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import com.codevern.bmssykharuii.data.SessionManager
import com.codevern.bmssykharuii.ui.screens.LoginScreen
import com.codevern.bmssykharuii.ui.theme.BMSSYKHARUIITheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        
        val sessionManager = SessionManager(this)

        setContent {
            BMSSYKHARUIITheme {
                val agentId by sessionManager.loggedInAgentId.collectAsState(initial = "LOADING")

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