package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Numbers
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
data class PfListMergedItem(
    val id: Int,
    val beneficiary_name: String,
    val approved_ssin: String,
    val period_form: String,
    val period_to: String,
    val last_update: String,
    val date_of_60: String
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PfUpdateListScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var mergedData by remember { mutableStateOf<List<PfListMergedItem>>(emptyList()) }
    var isLoading by remember { mutableStateOf(false) }
    var searchQuery by remember { mutableStateOf("") }
    
    // Type Filter (All, 142, 242)
    var typeFilter by remember { mutableStateOf("All") }
    var dateFrom by remember { mutableStateOf("") }
    var dateTo by remember { mutableStateOf("") }
    
    var offset by remember { mutableStateOf(0) }
    var hasSearched by remember { mutableStateOf(false) }

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

    fun loadData(isLoadMore: Boolean = false) {
        if (!isLoadMore) {
            offset = 0
            mergedData = emptyList()
        }
        hasSearched = true
        isLoading = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    // Fetch Pf Updates (Accepted only)
                    val pfUpdates = SupabaseApi.client.from("pf_update").select {
                        filter {
                            eq("reason", "Accepted")
                            if (typeFilter != "All") {
                                like("approved_ssin", "$typeFilter%")
                            }
                            if (dateFrom.isNotBlank()) {
                                gte("period_form", dateFrom)
                            }
                            if (dateTo.isNotBlank()) {
                                lte("period_to", dateTo)
                            }
                        }
                        order("id", io.github.jan.supabase.postgrest.query.Order.DESCENDING)
                        range(offset.toLong(), offset.toLong() + 49L)
                    }.decodeList<PfUpdateEntry>()

                    if (pfUpdates.isNotEmpty()) {
                        val ssins = pfUpdates.mapNotNull { it.approved_ssin }.distinct()
                        // Fetch Beneficiaries to get date_of_60
                        val beneficiaries = SupabaseApi.client.from("beneficiaries").select {
                            filter { isIn("approved_ssin", ssins) }
                        }.decodeList<BeneficiaryItem>()

                        val bMap = beneficiaries.associateBy { it.approved_ssin }

                        val mappedData = pfUpdates.map { pf ->
                            PfListMergedItem(
                                id = pf.id ?: 0,
                                beneficiary_name = pf.beneficiary_name ?: "Unknown",
                                approved_ssin = pf.approved_ssin ?: "N/A",
                                period_form = pf.period_form ?: "-",
                                period_to = pf.period_to ?: "-",
                                last_update = "Recent",
                                date_of_60 = bMap[pf.approved_ssin]?.date_of_attaining_60 ?: "N/A"
                            )
                        }
                        if (isLoadMore) {
                            mergedData = mergedData + mappedData
                        } else {
                            mergedData = mappedData
                        }
                    } else {
                        if (!isLoadMore) mergedData = emptyList()
                    }
                }
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Failed to load data: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(isAtBottom) {
        if (isAtBottom && !isLoading && mergedData.isNotEmpty()) {
            offset += 50
            loadData(isLoadMore = true)
        }
    }

    val filteredList = mergedData.filter {
        it.beneficiary_name.contains(searchQuery, ignoreCase = true) ||
        it.approved_ssin.contains(searchQuery, ignoreCase = true)
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(16.dp)
    ) {
        Text(
            text = "PF Update Ledger",
            fontSize = 20.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary,
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
                placeholder = { Text("Search Name, SSIN...") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = MaterialTheme.colorScheme.primary) },
                singleLine = true,
                shape = RoundedCornerShape(100.dp),
                colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, 
                    focusedBorderColor = MaterialTheme.colorScheme.primary,
                    focusedContainerColor = MaterialTheme.colorScheme.surface,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surface
                )
            )

            FloatingActionButton(
                onClick = { loadData() },
                containerColor = MaterialTheme.colorScheme.primary,
                contentColor = MaterialTheme.colorScheme.onPrimary,
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

        // Date Filters
        Row(modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            DatePickerField(
                label = "Period From",
                date = dateFrom,
                onDateSelected = { dateFrom = it },
                modifier = Modifier.weight(1f),
                primaryColor = MaterialTheme.colorScheme.primary
            )
            DatePickerField(
                label = "Period To",
                date = dateTo,
                onDateSelected = { dateTo = it },
                modifier = Modifier.weight(1f),
                primaryColor = MaterialTheme.colorScheme.primary
            )
        }

        // Type Filter Tabs & Search
        Row(modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.weight(1f)) {
                listOf("All", "142", "242").forEach { type ->
                    val isSelected = typeFilter == type
                    FilterChip(
                        selected = isSelected,
                        onClick = { typeFilter = type },
                        label = { Text(if (type == "All") "All" else if (type == "142") "Others" else "Construction") },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = MaterialTheme.colorScheme.primary,
                            selectedLabelColor = MaterialTheme.colorScheme.onPrimary
                        )
                    )
                }
            }
            Button(
                onClick = { loadData(isLoadMore = false) },
                shape = RoundedCornerShape(100.dp),
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary)
            ) {
                Text("Search")
            }
        }

        Card(
            modifier = Modifier.fillMaxSize(),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            shape = RoundedCornerShape(16.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            if (isLoading && mergedData.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            } else if (!hasSearched) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("Enter filters and click Search to load data.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            } else if (filteredList.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No records found.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            } else {
                LazyColumn(
                    state = listState,
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(filteredList) { item ->
                        PfUpdateLedgerCard(item = item)
                    }
                    if (isLoading && mergedData.isNotEmpty()) {
                        item {
                            Box(modifier = Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
                                CircularProgressIndicator(color = MaterialTheme.colorScheme.primary, modifier = Modifier.size(24.dp))
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun PfUpdateLedgerCard(item: PfListMergedItem) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f)),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier.size(40.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primaryContainer),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(Icons.Default.CheckCircle, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(20.dp))
                }
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    Text(item.beneficiary_name, fontSize = 16.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                    Spacer(modifier = Modifier.height(2.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Numbers, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(12.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(item.approved_ssin, fontSize = 13.sp, color = MaterialTheme.colorScheme.primary, fontFamily = FontFamily.Monospace, fontWeight = FontWeight.SemiBold)
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            HorizontalDivider(color = MaterialTheme.colorScheme.outline.copy(alpha = 0.5f))
            Spacer(modifier = Modifier.height(12.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Period From", fontSize = 11.sp, color = Color(0xFF16A34A), fontWeight = FontWeight.Bold)
                    Text(item.period_form, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface, fontWeight = FontWeight.Medium)
                }
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("Period To", fontSize = 11.sp, color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                    Text(item.period_to, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface, fontWeight = FontWeight.Medium)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Date of 60", fontSize = 11.sp, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                    Text(item.date_of_60, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface, fontWeight = FontWeight.Medium)
                }
            }
        }
    }
}
