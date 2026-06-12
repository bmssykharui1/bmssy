package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.slideInVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
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
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.codevern.bmssykharuii.network.SupabaseApi
import io.github.jan.supabase.postgrest.from
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AddNewBeneficiaryScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var ssin by remember { mutableStateOf("") }
    var isChecking by remember { mutableStateOf(false) }
    var isSubmitting by remember { mutableStateOf(false) }

    var showNewDataForm by remember { mutableStateOf(false) }
    var existingProfile by remember { mutableStateOf<BeneficiaryItem?>(null) }

    var name by remember { mutableStateOf("") }
    var dateOf60 by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }

    val primaryGreen = Color(0xFF146C2E)
    val primaryBlue = Color(0xFF0B57D0)

    fun resetForm() {
        ssin = ""
        name = ""
        dateOf60 = ""
        phone = ""
        showNewDataForm = false
        existingProfile = null
    }

    fun verifySSIN() {
        if (ssin.length != 12) {
            Toast.makeText(context, "SSIN must be exactly 12 digits", Toast.LENGTH_SHORT).show()
            return
        }

        isChecking = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val result = SupabaseApi.client.from("beneficiaries").select {
                        filter { eq("approved_ssin", ssin) }
                    }.decodeSingleOrNull<BeneficiaryItem>()
                    
                    if (result != null) {
                        existingProfile = result
                        showNewDataForm = false
                        withContext(Dispatchers.Main) {
                            Toast.makeText(context, "SSIN Found in Database", Toast.LENGTH_SHORT).show()
                        }
                    } else {
                        existingProfile = null
                        showNewDataForm = true
                        withContext(Dispatchers.Main) {
                            Toast.makeText(context, "New SSIN. Please fill the details.", Toast.LENGTH_SHORT).show()
                        }
                    }
                }
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Error checking SSIN", Toast.LENGTH_SHORT).show()
            } finally {
                isChecking = false
            }
        }
    }

    fun submitNewData() {
        if (name.isBlank() || dateOf60.isBlank() || phone.isBlank()) {
            Toast.makeText(context, "Please fill all fields", Toast.LENGTH_SHORT).show()
            return
        }

        isSubmitting = true
        coroutineScope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val newEntry = BeneficiaryItem(
                        beneficiary_name = name,
                        approved_ssin = ssin,
                        date_of_attaining_60 = dateOf60,
                        phone_no = phone
                    )
                    SupabaseApi.client.from("beneficiaries").insert(newEntry)
                }
                Toast.makeText(context, "Data Saved Successfully!", Toast.LENGTH_LONG).show()
                resetForm()
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(context, "Failed to save: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                isSubmitting = false
            }
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.padding(bottom = 24.dp)
        ) {
            Icon(Icons.Default.AddCircle, contentDescription = null, tint = primaryBlue, modifier = Modifier.size(32.dp))
            Spacer(modifier = Modifier.width(8.dp))
            Text("Add New SSIN", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = primaryBlue)
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
        ) {
            Column(modifier = Modifier.padding(24.dp)) {
                Text("SSIN PORTAL DATA ENTRY", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = primaryBlue)
                Spacer(modifier = Modifier.height(16.dp))

                OutlinedTextField(
                    value = ssin,
                    onValueChange = { if (it.length <= 12 && it.all { char -> char.isDigit() }) ssin = it },
                    label = { Text("SSIN Number") },
                    placeholder = { Text("Enter 12-digit SSIN") },
                    leadingIcon = { Icon(Icons.Default.Numbers, contentDescription = null, tint = primaryBlue) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    readOnly = showNewDataForm || isChecking || isSubmitting,
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryBlue, focusedLabelColor = primaryBlue)
                )

                AnimatedVisibility(
                    visible = showNewDataForm,
                    enter = fadeIn() + slideInVertically()
                ) {
                    Column(modifier = Modifier.padding(top = 16.dp)) {
                        OutlinedTextField(
                            value = name,
                            onValueChange = { name = it.uppercase() },
                            label = { Text("Beneficiary Name") },
                            leadingIcon = { Icon(Icons.Default.Person, contentDescription = null, tint = primaryBlue) },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Characters),
                            colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryBlue, focusedLabelColor = primaryBlue)
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        DatePickerField(
                            label = "Date of Attaining 60",
                            date = dateOf60,
                            onDateSelected = { dateOf60 = it },
                            modifier = Modifier.fillMaxWidth(),
                            primaryColor = primaryBlue
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        OutlinedTextField(
                            value = phone,
                            onValueChange = { if (it.length <= 10 && it.all { char -> char.isDigit() }) phone = it },
                            label = { Text("Phone Number") },
                            leadingIcon = { Icon(Icons.Default.Phone, contentDescription = null, tint = primaryBlue) },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                            colors = OutlinedTextFieldDefaults.colors(focusedTextColor = Color.Black, unfocusedTextColor = Color.Black, focusedBorderColor = primaryBlue, focusedLabelColor = primaryBlue)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(24.dp))

                if (!showNewDataForm) {
                    Button(
                        onClick = { verifySSIN() },
                        modifier = Modifier.fillMaxWidth().height(56.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = primaryBlue),
                        enabled = !isChecking && ssin.length == 12
                    ) {
                        if (isChecking) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Checking SSIN...")
                        } else {
                            Icon(Icons.Default.Search, contentDescription = null)
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Verify Database", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                } else {
                    Button(
                        onClick = { submitNewData() },
                        modifier = Modifier.fillMaxWidth().height(56.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = primaryGreen),
                        enabled = !isSubmitting
                    ) {
                        if (isSubmitting) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Processing Data...")
                        } else {
                            Icon(Icons.Default.CheckCircle, contentDescription = null)
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Submit & Create Entry", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                    Spacer(modifier = Modifier.height(12.dp))
                    TextButton(
                        onClick = { resetForm() },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Cancel", color = Color.Gray, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }

        // Existing Profile Card
        AnimatedVisibility(
            visible = existingProfile != null,
            enter = fadeIn() + slideInVertically()
        ) {
            existingProfile?.let { profile ->
                Card(
                    modifier = Modifier.fillMaxWidth().padding(top = 24.dp),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = primaryGreen),
                    elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
                ) {
                    Column(modifier = Modifier.padding(24.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier.size(48.dp).clip(CircleShape).background(Color.White.copy(alpha = 0.2f)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(Icons.Default.Person, contentDescription = null, tint = Color.White, modifier = Modifier.size(24.dp))
                            }
                            Spacer(modifier = Modifier.width(16.dp))
                            Column {
                                Text("SSIN Profile", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                                Text(profile.beneficiary_name ?: "", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.ExtraBold)
                            }
                        }
                        Spacer(modifier = Modifier.height(16.dp))
                        Divider(color = Color.White.copy(alpha = 0.3f))
                        Spacer(modifier = Modifier.height(16.dp))
                        
                        ProfileRow(icon = Icons.Default.Numbers, label = "SSIN", value = profile.approved_ssin ?: "")
                        ProfileRow(icon = Icons.Default.CalendarToday, label = "Age 60 Date", value = profile.date_of_attaining_60 ?: "")
                        ProfileRow(icon = Icons.Default.Phone, label = "Mobile", value = profile.phone_no ?: "")
                        
                        Spacer(modifier = Modifier.height(24.dp))
                        Button(
                            onClick = { resetForm() },
                            colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = primaryGreen),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Search Another SSIN", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ProfileRow(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(icon, contentDescription = null, tint = Color.White.copy(alpha = 0.8f), modifier = Modifier.size(16.dp))
            Spacer(modifier = Modifier.width(8.dp))
            Text(label, color = Color.White.copy(alpha = 0.8f), fontSize = 14.sp)
        }
        Text(value, color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Monospace)
    }
}
