package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Call
import androidx.compose.material.icons.filled.Numbers
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
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
data class BeneficiaryItem(
    val id: Int? = null,
    val beneficiary_name: String? = null,
    val approved_ssin: String? = null,
    val date_of_attaining_60: String? = null,
    val phone_no: String? = null,
    val remark: String? = null
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AllDataListScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var beneficiaries by remember { mutableStateOf<List<BeneficiaryItem>>(emptyList()) }
    var isLoading by remember { mutableStateOf(true) }
    var searchQuery by remember { mutableStateOf("") }

    val primaryBlue = Color(0xFF0B57D0)

    var isAdvancedSearch by remember { mutableStateOf(false) }
    var advSsin by remember { mutableStateOf("") }
    var advName by remember { mutableStateOf("") }
    var advPhone by remember { mutableStateOf("") }

    fun loadData() {
        isLoading = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val result = SupabaseApi.client.from("beneficiaries").select {
                        if (isAdvancedSearch && (advSsin.isNotBlank() || advName.isNotBlank() || advPhone.isNotBlank())) {
                            filter {
                                if (advSsin.isNotBlank()) like("approved_ssin", "%$advSsin%")
                                if (advName.isNotBlank()) ilike("beneficiary_name", "%$advName%")
                                if (advPhone.isNotBlank()) like("phone_no", "%$advPhone%")
                            }
                        }
                        order("id", io.github.jan.supabase.postgrest.query.Order.DESCENDING)
                        limit(100) 
                    }.decodeList<BeneficiaryItem>()
                    beneficiaries = result
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

    val filteredList = if (isAdvancedSearch) beneficiaries else beneficiaries.filter {
        (it.beneficiary_name?.contains(searchQuery, ignoreCase = true) == true) ||
        (it.approved_ssin?.contains(searchQuery, ignoreCase = true) == true) ||
        (it.phone_no?.contains(searchQuery, ignoreCase = true) == true)
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF8FAFC))
            .padding(16.dp)
    ) {
        Text(
            text = "Beneficiary Database",
            fontSize = 20.sp,
            fontWeight = FontWeight.Bold,
            color = primaryBlue,
            modifier = Modifier.padding(bottom = 8.dp)
        )

        Row(
            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Checkbox(
                checked = isAdvancedSearch,
                onCheckedChange = { isAdvancedSearch = it },
                colors = CheckboxDefaults.colors(checkedColor = primaryBlue)
            )
            Text("Advanced Search", fontSize = 14.sp, fontWeight = FontWeight.Medium, color = Color(0xFF334155))
        }

        if (isAdvancedSearch) {
            Card(
                modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
                shape = RoundedCornerShape(12.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    OutlinedTextField(
                        value = advSsin,
                        onValueChange = { advSsin = it },
                        label = { Text("SSIN Number") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryBlue, focusedLabelColor = primaryBlue)
                    )
                    OutlinedTextField(
                        value = advName,
                        onValueChange = { advName = it },
                        label = { Text("Name") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryBlue, focusedLabelColor = primaryBlue)
                    )
                    OutlinedTextField(
                        value = advPhone,
                        onValueChange = { advPhone = it },
                        label = { Text("Phone No") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryBlue, focusedLabelColor = primaryBlue)
                    )
                    Button(
                        onClick = { loadData() },
                        modifier = Modifier.fillMaxWidth().height(48.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = primaryBlue),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Icon(Icons.Default.Search, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Search Manually", fontWeight = FontWeight.Bold)
                    }
                }
            }
        } else {
            // Search Bar and Refresh
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    modifier = Modifier.weight(1f),
                    placeholder = { Text("Search Name, SSIN, Phone...") },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = primaryBlue) },
                    singleLine = true,
                    shape = RoundedCornerShape(100.dp),
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, 
                        focusedBorderColor = primaryBlue,
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color.White,
                        unfocusedContainerColor = Color.White
                    )
                )

                FloatingActionButton(
                    onClick = { loadData() },
                    containerColor = primaryBlue,
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
        }

        // List
        Card(
            modifier = Modifier.fillMaxSize(),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            shape = RoundedCornerShape(16.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            if (isLoading && beneficiaries.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = primaryBlue)
                }
            } else if (filteredList.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No records found.", color = Color(0xFF94A3B8))
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(filteredList) { item ->
                        BeneficiaryCard(item = item)
                    }
                }
            }
        }
    }
}

@Composable
fun BeneficiaryCard(item: BeneficiaryItem) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFF8FAFC)),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(Color(0xFFDBEAFE)),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = item.beneficiary_name?.take(1)?.uppercase() ?: "?",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0B57D0)
                    )
                }
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    Text(
                        text = item.beneficiary_name ?: "Unknown",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A)
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Numbers, contentDescription = null, tint = Color(0xFF0B57D0), modifier = Modifier.size(12.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = item.approved_ssin ?: "N/A",
                            fontSize = 13.sp,
                            color = Color(0xFF0B57D0),
                            fontFamily = FontFamily.Monospace,
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            Divider(color = Color(0xFFE2E8F0))
            Spacer(modifier = Modifier.height(12.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Date of 60", fontSize = 11.sp, color = Color(0xFF64748B), fontWeight = FontWeight.Bold)
                    Text(item.date_of_attaining_60 ?: "-", fontSize = 13.sp, color = Color(0xFF0F172A))
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Phone", fontSize = 11.sp, color = Color(0xFF64748B), fontWeight = FontWeight.Bold)
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Call, contentDescription = null, modifier = Modifier.size(10.dp), tint = Color(0xFF64748B))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(item.phone_no ?: "-", fontSize = 13.sp, color = Color(0xFF0F172A))
                    }
                }
            }
        }
    }
}
