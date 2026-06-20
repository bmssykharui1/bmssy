package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Refresh
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
data class DSPfUpdateEntry(
    val beneficiary_name: String,
    val approved_ssin: String,
    val status: String,
    val beneficiary_id: Int,
    val period_form: String,
    val period_to: String,
    val ds_no: String,
    val ds_date: String,
    val reason: String? = null
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DuareSorkarPfUpdateScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var pendingData by remember { mutableStateOf<List<DsRecord>>(emptyList()) }
    var globalSettings by remember { mutableStateOf<GlobalSettings?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var searchQuery by remember { mutableStateOf("") }
    var offset by remember { mutableStateOf(0) }

    val listState = rememberLazyListState()

    val isAtBottom by remember {
        derivedStateOf {
            val layoutInfo = listState.layoutInfo
            val visibleItemsInfo = layoutInfo.visibleItemsInfo
            if (layoutInfo.totalItemsCount == 0) {
                false
            } else {
                val lastVisibleItem = visibleItemsInfo.last()
                lastVisibleItem.index + 1 == layoutInfo.totalItemsCount
            }
        }
    }

    val primaryColor = Color(0xFFB36B00)

    fun loadData(isLoadMore: Boolean = false) {
        if (!isLoadMore) {
            offset = 0
            pendingData = emptyList()
        }
        isLoading = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val settingsList = SupabaseApi.client.from("global_settings").select { filter { eq("id", 1) } }.decodeList<GlobalSettings>()
                    val settings = settingsList.firstOrNull() ?: GlobalSettings()
                    globalSettings = settings

                    val dsRecords = SupabaseApi.client.from("ds_record").select {
                        order("id", io.github.jan.supabase.postgrest.query.Order.DESCENDING)
                        range(offset.toLong(), offset.toLong() + 199L)
                    }.decodeList<DsRecord>()

                    if (dsRecords.isNotEmpty()) {
                        val ssins = dsRecords.mapNotNull { it.ssin }
                        val pfUpdates = SupabaseApi.client.from("pf_update").select {
                            filter { isIn("approved_ssin", ssins) }
                        }.decodeList<PfUpdateEntry>()

                        val globalPeriodTo = settings.period_to ?: ""

                        val pending = dsRecords.filter { d ->
                            val latestPf = pfUpdates.filter { it.approved_ssin == d.ssin }.maxByOrNull { it.id ?: 0 }
                            latestPf?.period_to != globalPeriodTo
                        }
                        if (isLoadMore) {
                            pendingData = pendingData + pending
                        } else {
                            pendingData = pending
                        }
                    } else {
                        if (!isLoadMore) pendingData = emptyList()
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

    LaunchedEffect(isAtBottom) {
        if (isAtBottom && !isLoading && pendingData.isNotEmpty()) {
            offset += 200
            loadData(isLoadMore = true)
        }
    }

    val filteredList = pendingData.filter { item ->
        (item.name?.contains(searchQuery, ignoreCase = true) == true) ||
        (item.ssin?.contains(searchQuery, ignoreCase = true) == true)
    }

    fun acceptRow(row: DsRecord, pfFrom: String, pfTo: String) {
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    // Get Beneficiary ID
                    val bList = SupabaseApi.client.from("beneficiaries").select {
                        filter { eq("approved_ssin", row.ssin ?: "") }
                    }.decodeList<BeneficiaryItem>()
                    
                    val bId = bList.firstOrNull()?.id ?: return@withContext

                    val newPf = DSPfUpdateEntry(
                        beneficiary_name = row.name ?: "",
                        approved_ssin = row.ssin ?: "",
                        status = "Accepted",
                        beneficiary_id = bId,
                        period_form = pfFrom,
                        period_to = pfTo,
                        ds_no = row.dsno ?: "",
                        ds_date = row.created_at ?: "",
                        reason = "Accepted"
                    )
                    SupabaseApi.client.from("pf_update").insert(newPf)
                }
                pendingData = pendingData.filter { it.ssin != row.ssin }
                Toast.makeText(context, "DS PF Updated Successfully", Toast.LENGTH_SHORT).show()
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Error accepting: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    Column(modifier = Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background).padding(16.dp)) {
        Text(text = "Duare Sorkar / PF Update", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = primaryColor, modifier = Modifier.padding(bottom = 16.dp))

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

            FloatingActionButton(onClick = { loadData(isLoadMore = false) }, containerColor = primaryColor, contentColor = Color.White, shape = CircleShape, modifier = Modifier.size(48.dp)) {
                if (isLoading) CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                else Icon(Icons.Default.Refresh, contentDescription = "Refresh")
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
                        Text("No pending DS PF updates.", color = Color.Gray)
                    }
                }
            } else {
                LazyColumn(state = listState, modifier = Modifier.fillMaxSize(), contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    items(filteredList) { item ->
                        DSPfPendingCard(
                            item = item,
                            globalFrom = globalSettings?.period_form ?: "",
                            globalTo = globalSettings?.period_to ?: "",
                            onAccept = { from, to -> acceptRow(item, from, to) }
                        )
                    }
                    if (isLoading && pendingData.isNotEmpty()) {
                        item {
                            Box(modifier = Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
                                CircularProgressIndicator(color = primaryColor, modifier = Modifier.size(24.dp))
                            }
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DSPfPendingCard(
    item: DsRecord, 
    globalFrom: String, 
    globalTo: String,
    onAccept: (String, String) -> Unit
) {
    var pFrom by remember { mutableStateOf(globalFrom) }
    var pTo by remember { mutableStateOf(globalTo) }
    var isSaving by remember { mutableStateOf(false) }

    Card(modifier = Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant), shape = RoundedCornerShape(12.dp)) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(modifier = Modifier.size(40.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primaryContainer), contentAlignment = Alignment.Center) {
                    Text(item.name?.take(1)?.uppercase() ?: "", color = MaterialTheme.colorScheme.onPrimaryContainer, fontWeight = FontWeight.Bold)
                }
                Spacer(modifier = Modifier.width(12.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Text(item.name ?: "Unknown", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    Text(item.ssin ?: "", color = Color(0xFFB36B00), fontFamily = FontFamily.Monospace, fontSize = 13.sp)
                }
            }

            Spacer(modifier = Modifier.height(12.dp))
            Row(modifier = Modifier.fillMaxWidth()) {
                Text("DS NO: ", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFFB36B00))
                Text(item.dsno ?: "-", fontSize = 12.sp, fontWeight = FontWeight.Bold)
            }
            Spacer(modifier = Modifier.height(12.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = pFrom,
                    onValueChange = { pFrom = it },
                    label = { Text("Period From") },
                    modifier = Modifier.weight(1f),
                    singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = Color(0xFFB36B00))
                )
                OutlinedTextField(
                    value = pTo,
                    onValueChange = { pTo = it },
                    label = { Text("Period To") },
                    modifier = Modifier.weight(1f),
                    singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = Color(0xFFB36B00))
                )
            }

            Spacer(modifier = Modifier.height(16.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                Button(
                    onClick = {
                        isSaving = true
                        onAccept(pFrom, pTo)
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFB36B00)),
                    modifier = Modifier.height(36.dp),
                    enabled = !isSaving,
                    contentPadding = PaddingValues(horizontal = 16.dp)
                ) {
                    if (isSaving) {
                        CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                    } else {
                        Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                    }
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(if (isSaving) "Saving..." else "Accept DS PF", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}
