package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Search
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
import kotlinx.serialization.Serializable

@Serializable
data class DsRecord(
    val id: Int? = null,
    val ssin: String? = null,
    val name: String? = null,
    val dsno: String? = null,
    val created_at: String? = null
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DuareSorkarEntryScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var pendingData by remember { mutableStateOf<List<BeneficiaryItem>>(emptyList()) }
    var isLoading by remember { mutableStateOf(true) }
    var searchQuery by remember { mutableStateOf("") }
    var categoryFilter by remember { mutableStateOf("All Categories") }

    val primaryColor = Color(0xFFB36B00)

    fun loadData() {
        isLoading = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val settingsList = SupabaseApi.client.from("global_settings").select { filter { eq("id", 1) } }.decodeList<GlobalSettings>()
                    val globalPeriodTo = settingsList.firstOrNull()?.period_to ?: ""

                    // Fetch active beneficiaries (142 or 242)
                    val allBeneficiaries = SupabaseApi.client.from("beneficiaries").select {
                        filter {
                            or {
                                like("approved_ssin", "142%")
                                like("approved_ssin", "242%")
                            }
                        }
                        order("id", io.github.jan.supabase.postgrest.query.Order.DESCENDING)
                        limit(200)
                    }.decodeList<BeneficiaryItem>()

                    val activeBeneficiaries = allBeneficiaries.filter { 
                        it.approved_ssin != null && (it.remark.isNullOrEmpty() || !it.remark.contains("reject", ignoreCase = true))
                    }

                    if (activeBeneficiaries.isNotEmpty()) {
                        val ssins = activeBeneficiaries.map { it.approved_ssin!! }
                        
                        // Fetch PF Updates
                        val pfUpdates = SupabaseApi.client.from("pf_update").select {
                            filter { isIn("approved_ssin", ssins) }
                        }.decodeList<PfUpdateEntry>()

                        // Fetch existing DS Records
                        val dsRecords = SupabaseApi.client.from("ds_record").select {
                            filter { isIn("ssin", ssins) }
                        }.decodeList<DsRecord>()

                        val existingDsSsins = dsRecords.mapNotNull { it.ssin }

                        val pending = activeBeneficiaries.filter { b ->
                            val latestPf = pfUpdates.filter { it.approved_ssin == b.approved_ssin }.maxByOrNull { it.id ?: 0 }
                            val hasPfForPeriod = (latestPf?.period_to == globalPeriodTo)
                            val hasDs = existingDsSsins.contains(b.approved_ssin)
                            
                            // Candidates are those who DO NOT have PF Update for period OR don't have DS record
                            // Wait, logic says: "Skip if they already have a PF update for the current period"
                            !hasPfForPeriod
                        }
                        pendingData = pending
                    } else {
                        pendingData = emptyList()
                    }
                }
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Failed to load data", Toast.LENGTH_SHORT).show()
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(Unit) {
        loadData()
    }

    val filteredList = pendingData.filter { item ->
        val matchesSearch = (item.beneficiary_name?.contains(searchQuery, ignoreCase = true) == true) ||
                            (item.approved_ssin?.contains(searchQuery, ignoreCase = true) == true)
        val matchesCategory = when (categoryFilter) {
            "142" -> item.approved_ssin?.startsWith("142") == true
            "242" -> item.approved_ssin?.startsWith("242") == true
            else -> true
        }
        matchesSearch && matchesCategory
    }

    fun saveDSEntry(ssin: String, name: String, dsno: String) {
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val cleanDsno = dsno.replace(Regex("\\D"), "")
                    
                    val existing = SupabaseApi.client.from("ds_record").select { filter { eq("ssin", ssin) } }.decodeList<DsRecord>()
                    if (existing.isNotEmpty()) {
                        val updateMap = mapOf("dsno" to cleanDsno)
                        SupabaseApi.client.from("ds_record").update(updateMap) { filter { eq("ssin", ssin) } }
                    } else {
                        val newRecord = DsRecord(ssin = ssin, name = name, dsno = cleanDsno)
                        SupabaseApi.client.from("ds_record").insert(newRecord)
                    }
                }
                pendingData = pendingData.filter { it.approved_ssin != ssin }
                Toast.makeText(context, "DS Entry Saved Successfully", Toast.LENGTH_SHORT).show()
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    Column(modifier = Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background).padding(16.dp)) {
        Text(text = "Duare Sorkar / Entry", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = primaryColor, modifier = Modifier.padding(bottom = 16.dp))

        Row(modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            OutlinedTextField(
                value = searchQuery,
                onValueChange = { searchQuery = it },
                modifier = Modifier.weight(1f),
                placeholder = { Text("Search Name or SSIN...") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = primaryColor) },
                singleLine = true,
                shape = RoundedCornerShape(100.dp),
                colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = primaryColor, focusedContainerColor = MaterialTheme.colorScheme.surface, unfocusedContainerColor = MaterialTheme.colorScheme.surface)
            )

            FloatingActionButton(onClick = { loadData() }, containerColor = primaryColor, contentColor = Color.White, shape = CircleShape, modifier = Modifier.size(48.dp)) {
                if (isLoading) CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                else Icon(Icons.Default.Refresh, contentDescription = "Refresh")
            }
        }

        // Category Filter
        Row(modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf("All Categories", "142", "242").forEach { type ->
                FilterChip(
                    selected = categoryFilter == type,
                    onClick = { categoryFilter = type },
                    label = { Text(if (type == "All Categories") "All Categories" else if (type == "142") "Others (142)" else "Constructions (242)") },
                    colors = FilterChipDefaults.filterChipColors(selectedContainerColor = primaryColor, selectedLabelColor = Color.White)
                )
            }
        }

        Card(modifier = Modifier.fillMaxSize(), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface), shape = RoundedCornerShape(16.dp), elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)) {
            if (isLoading && pendingData.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = primaryColor)
                }
            } else if (filteredList.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.CheckCircle, contentDescription = null, tint = Color(0xFF16A34A), modifier = Modifier.size(48.dp))
                        Spacer(modifier = Modifier.height(16.dp))
                        Text("All Caught Up!", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                        Text("No pending candidates for DS Entry.", color = Color.Gray)
                    }
                }
            } else {
                LazyColumn(modifier = Modifier.fillMaxSize(), contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    items(filteredList) { item ->
                        DSEntryCard(item = item, onSave = { dsno -> saveDSEntry(item.approved_ssin ?: "", item.beneficiary_name ?: "", dsno) })
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DSEntryCard(item: BeneficiaryItem, onSave: (String) -> Unit) {
    var dsno by remember { mutableStateOf("") }
    var isSaving by remember { mutableStateOf(false) }

    Card(modifier = Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant), shape = RoundedCornerShape(12.dp)) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(modifier = Modifier.size(40.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primaryContainer), contentAlignment = Alignment.Center) {
                    Text(item.beneficiary_name?.take(1)?.uppercase() ?: "", color = MaterialTheme.colorScheme.onPrimaryContainer, fontWeight = FontWeight.Bold)
                }
                Spacer(modifier = Modifier.width(12.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Text(item.beneficiary_name ?: "Unknown", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    Text(item.approved_ssin ?: "", color = Color(0xFFB36B00), fontFamily = FontFamily.Monospace, fontSize = 13.sp)
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = dsno,
                    onValueChange = { dsno = it },
                    label = { Text("DS NO") },
                    modifier = Modifier.weight(1f),
                    singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = Color(0xFFB36B00))
                )
                
                Button(
                    onClick = { 
                        if (dsno.isNotBlank()) {
                            isSaving = true
                            onSave(dsno)
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFB36B00)),
                    modifier = Modifier.height(56.dp).padding(top = 6.dp),
                    enabled = !isSaving && dsno.isNotBlank()
                ) {
                    if (isSaving) {
                        CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                    } else {
                        Icon(Icons.Default.Save, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Save", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
