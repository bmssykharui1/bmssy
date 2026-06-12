package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.codevern.bmssykharuii.network.SupabaseApi
import io.github.jan.supabase.postgrest.from
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PfUpdationOthersScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var pendingData by remember { mutableStateOf<List<BeneficiaryItem>>(emptyList()) }
    var globalSettings by remember { mutableStateOf<GlobalSettings?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var searchQuery by remember { mutableStateOf("") }

    // Reject Modal State
    var showRejectModal by remember { mutableStateOf(false) }
    var rejectingSsin by remember { mutableStateOf("") }
    var rejectingName by remember { mutableStateOf("") }
    var rejectReason by remember { mutableStateOf("Duplicate SSIN") }
    var isProcessingReject by remember { mutableStateOf(false) }

    val primaryColor = Color(0xFF6750A4)

    fun loadData() {
        isLoading = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val settingsList = SupabaseApi.client.from("global_settings").select {
                        filter { eq("id", 1) }
                    }.decodeList<GlobalSettings>()
                    
                    val settings = settingsList.firstOrNull() ?: GlobalSettings()
                    globalSettings = settings

                    // Others prefix = 142
                    val allBeneficiaries = SupabaseApi.client.from("beneficiaries").select {
                        filter { like("approved_ssin", "142%") }
                        order("id", io.github.jan.supabase.postgrest.query.Order.DESCENDING)
                        limit(200)
                    }.decodeList<BeneficiaryItem>()

                    val activeBeneficiaries = allBeneficiaries.filter { 
                        it.approved_ssin != null && (it.remark.isNullOrEmpty() || !it.remark.contains("reject", ignoreCase = true))
                    }

                    if (activeBeneficiaries.isNotEmpty()) {
                        val ssins = activeBeneficiaries.map { it.approved_ssin!! }
                        val pfUpdates = SupabaseApi.client.from("pf_update").select {
                            filter { isIn("approved_ssin", ssins) }
                        }.decodeList<PfUpdateEntry>()

                        val globalPeriodTo = settings.period_to ?: ""

                        val pending = activeBeneficiaries.filter { b ->
                            val latestUpdate = pfUpdates.filter { it.approved_ssin == b.approved_ssin }
                                .maxByOrNull { it.id ?: 0 }
                            latestUpdate?.period_to != globalPeriodTo
                        }
                        pendingData = pending
                    } else {
                        pendingData = emptyList()
                    }
                }
            } catch (e: Exception) {
                e.printStackTrace()
                withContext(Dispatchers.Main) {
                    Toast.makeText(context, "Failed to load data: ${e.message}", Toast.LENGTH_LONG).show()
                }
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(Unit) {
        loadData()
    }

    val filteredList = pendingData.filter {
        (it.beneficiary_name?.contains(searchQuery, ignoreCase = true) == true) ||
        (it.approved_ssin?.contains(searchQuery, ignoreCase = true) == true)
    }

    fun acceptRow(row: BeneficiaryItem, pfFrom: String, pfTo: String) {
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val newPf = PfUpdateEntry(
                        beneficiary_name = row.beneficiary_name,
                        approved_ssin = row.approved_ssin,
                        beneficiary_id = row.id,
                        period_form = pfFrom,
                        period_to = pfTo,
                        reason = "Accepted"
                    )
                    SupabaseApi.client.from("pf_update").insert(newPf)
                }
                pendingData = pendingData.filter { it.approved_ssin != row.approved_ssin }
                Toast.makeText(context, "PF Updated Successfully", Toast.LENGTH_SHORT).show()
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Error accepting: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    fun rejectRow() {
        if (rejectingSsin.isEmpty()) return
        isProcessingReject = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val bList = pendingData.filter { it.approved_ssin == rejectingSsin }
                    if (bList.isNotEmpty()) {
                        val bId = bList.first().id
                        val rejectPf = PfUpdateEntry(
                            beneficiary_name = rejectingName,
                            approved_ssin = rejectingSsin,
                            beneficiary_id = bId,
                            reason = "Rejected: $rejectReason"
                        )
                        SupabaseApi.client.from("pf_update").insert(rejectPf)
                        
                        val remarkMap = mapOf("remark" to "Rejected: $rejectReason")
                        SupabaseApi.client.from("beneficiaries").update(remarkMap) {
                            filter { eq("approved_ssin", rejectingSsin) }
                        }
                    }
                }
                pendingData = pendingData.filter { it.approved_ssin != rejectingSsin }
                showRejectModal = false
                Toast.makeText(context, "Beneficiary Rejected", Toast.LENGTH_SHORT).show()
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Error rejecting: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                isProcessingReject = false
            }
        }
    }

    Box(modifier = Modifier.fillMaxSize()) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(Color(0xFFF8FAFC))
                .padding(16.dp)
        ) {
            Text(
                text = "PF Updation / Others (142)",
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold,
                color = primaryColor,
                modifier = Modifier.padding(bottom = 16.dp)
            )

            Row(
                modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    modifier = Modifier.weight(1f),
                    placeholder = { Text("Search by Name, SSIN...") },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = primaryColor) },
                    singleLine = true,
                    shape = RoundedCornerShape(100.dp),
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryColor, focusedContainerColor = Color.White, unfocusedContainerColor = Color.White)
                )

                FloatingActionButton(
                    onClick = { loadData() },
                    containerColor = primaryColor,
                    contentColor = Color.White,
                    shape = CircleShape,
                    modifier = Modifier.size(48.dp)
                ) {
                    if (isLoading) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                    } else {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                    }
                }
            }

            Card(
                modifier = Modifier.fillMaxSize(),
                colors = CardDefaults.cardColors(containerColor = Color.White),
                shape = RoundedCornerShape(16.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                if (isLoading && pendingData.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            CircularProgressIndicator(color = primaryColor)
                            Spacer(modifier = Modifier.height(16.dp))
                            Text("Scanning for Pending Records...", color = Color.Gray)
                        }
                    }
                } else if (filteredList.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Icon(Icons.Default.CheckCircle, contentDescription = null, tint = Color(0xFF146C2E), modifier = Modifier.size(48.dp))
                            Spacer(modifier = Modifier.height(16.dp))
                            Text("All Caught Up!", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                            Text("No pending PF updates found.", color = Color.Gray)
                        }
                    }
                } else {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(filteredList) { item ->
                            PfPendingCard(
                                item = item,
                                globalFrom = globalSettings?.period_form ?: "",
                                globalTo = globalSettings?.period_to ?: "",
                                onAccept = { from, to -> acceptRow(item, from, to) },
                                onReject = { 
                                    rejectingSsin = item.approved_ssin ?: ""
                                    rejectingName = item.beneficiary_name ?: ""
                                    showRejectModal = true
                                }
                            )
                        }
                    }
                }
            }
        }

        if (showRejectModal) {
            Box(
                modifier = Modifier.fillMaxSize().background(Color.Black.copy(alpha = 0.5f)),
                contentAlignment = Alignment.Center
            ) {
                Card(
                    modifier = Modifier.padding(24.dp).fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White)
                ) {
                    Column(modifier = Modifier.padding(24.dp)) {
                        Text("Reject Beneficiary", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = Color(0xFFB3261E))
                        Spacer(modifier = Modifier.height(16.dp))
                        
                        Text(rejectingName, fontWeight = FontWeight.SemiBold)
                        Text(rejectingSsin, fontFamily = FontFamily.Monospace, color = Color(0xFFB3261E))
                        
                        Spacer(modifier = Modifier.height(16.dp))
                        Text("Reason for Rejection", fontSize = 12.sp, color = Color.Gray)
                        Spacer(modifier = Modifier.height(8.dp))
                        
                        val reasons = listOf("Duplicate SSIN", "Document Mismatch", "Fake Details", "Already Exists", "Other")
                        reasons.forEach { reason ->
                            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                                RadioButton(
                                    selected = (rejectReason == reason),
                                    onClick = { rejectReason = reason },
                                    colors = RadioButtonDefaults.colors(selectedColor = Color(0xFFB3261E))
                                )
                                Text(reason, fontSize = 14.sp)
                            }
                        }

                        Spacer(modifier = Modifier.height(24.dp))
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                            TextButton(onClick = { showRejectModal = false }) {
                                Text("Cancel", color = Color.Gray)
                            }
                            Spacer(modifier = Modifier.width(8.dp))
                            Button(
                                onClick = { rejectRow() },
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFB3261E)),
                                enabled = !isProcessingReject
                            ) {
                                if (isProcessingReject) CircularProgressIndicator(modifier = Modifier.size(20.dp), color = Color.White)
                                else Text("Confirm Reject")
                            }
                        }
                    }
                }
            }
        }
    }
}
