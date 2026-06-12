package com.codevern.bmssykharuii.ui.screens

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarToday
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
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
import kotlinx.serialization.Serializable
import java.time.LocalDate
import java.time.format.DateTimeFormatter
import android.app.DatePickerDialog
import java.util.Calendar

@Serializable
data class Form4Entry(
    val reg_no: String,
    val beneficiary_name: String,
    val book_no: String,
    val receipt_no: String,
    val for_month: String,
    val date_of_collection: String,
    val amount: Double
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun Form4AddNewScreen() {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    var regNo by remember { mutableStateOf("") }
    var beneficiaryName by remember { mutableStateOf("") }
    var bookNo by remember { mutableStateOf("") }
    var receiptNo by remember { mutableStateOf("") }
    var forMonthFrom by remember { mutableStateOf("") }
    var forMonthTo by remember { mutableStateOf("") }
    var dateOfCollection by remember { mutableStateOf(LocalDate.now().toString()) }
    var amount by remember { mutableStateOf("") }

    var isLoading by remember { mutableStateOf(false) }

    val primaryTeal = Color(0xFF0D9488)

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp)
    ) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            shape = RoundedCornerShape(16.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
        ) {
            Column(
                modifier = Modifier.padding(24.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Text(
                    text = "Add New Form 4 Entry",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    color = primaryTeal,
                    modifier = Modifier.padding(bottom = 8.dp)
                )

                // Registration No
                OutlinedTextField(
                    value = regNo,
                    onValueChange = { regNo = it },
                    label = { Text("Registration No.") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = primaryTeal, focusedLabelColor = primaryTeal)
                )

                // Beneficiary Name
                OutlinedTextField(
                    value = beneficiaryName,
                    onValueChange = { beneficiaryName = it.uppercase() },
                    label = { Text("Name of Beneficiary") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Characters),
                    colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = primaryTeal, focusedLabelColor = primaryTeal)
                )

                // Book No and Receipt No Row
                Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    OutlinedTextField(
                        value = bookNo,
                        onValueChange = { bookNo = it },
                        label = { Text("Book No.") },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = primaryTeal, focusedLabelColor = primaryTeal)
                    )
                    OutlinedTextField(
                        value = receiptNo,
                        onValueChange = { receiptNo = it },
                        label = { Text("Receipt No.") },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = primaryTeal, focusedLabelColor = primaryTeal)
                    )
                }

                Text("For the Period (YYYY-MM-DD)", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Row(horizontalArrangement = Arrangement.spacedBy(16.dp), verticalAlignment = Alignment.CenterVertically) {
                    DatePickerField(
                        label = "From",
                        date = forMonthFrom,
                        onDateSelected = { forMonthFrom = it },
                        modifier = Modifier.weight(1f),
                        primaryColor = primaryTeal
                    )
                    Text("TO", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    DatePickerField(
                        label = "To",
                        date = forMonthTo,
                        onDateSelected = { forMonthTo = it },
                        modifier = Modifier.weight(1f),
                        primaryColor = primaryTeal
                    )
                }

                // Date of Collection & Amount
                Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    DatePickerField(
                        label = "Date of Collection",
                        date = dateOfCollection,
                        onDateSelected = { dateOfCollection = it },
                        modifier = Modifier.weight(1f),
                        primaryColor = primaryTeal
                    )
                    OutlinedTextField(
                        value = amount,
                        onValueChange = { amount = it },
                        label = { Text("Amount (₹)") },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, focusedBorderColor = primaryTeal, focusedLabelColor = primaryTeal)
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                // Save Button
                Button(
                    onClick = {
                        if (regNo.isBlank() || beneficiaryName.isBlank() || bookNo.isBlank() || receiptNo.isBlank() || forMonthFrom.isBlank() || forMonthTo.isBlank() || amount.isBlank()) {
                            Toast.makeText(context, "Please fill all fields", Toast.LENGTH_SHORT).show()
                            return@Button
                        }

                        isLoading = true
                        coroutineScope.launch {
                            try {
                                val fromParts = forMonthFrom.split("-")
                                val toParts = forMonthTo.split("-")
                                val forMonth = if (fromParts.size == 3 && toParts.size == 3) {
                                    "${fromParts[2]}-${fromParts[1]}-${fromParts[0]} - ${toParts[2]}-${toParts[1]}-${toParts[0]}"
                                } else {
                                    "$forMonthFrom - $forMonthTo"
                                }

                                val entry = Form4Entry(
                                    reg_no = regNo,
                                    beneficiary_name = beneficiaryName,
                                    book_no = bookNo,
                                    receipt_no = receiptNo,
                                    for_month = forMonth,
                                    date_of_collection = dateOfCollection,
                                    amount = amount.toDoubleOrNull() ?: 0.0
                                )

                                withContext(Dispatchers.IO) {
                                    SupabaseApi.client.from("form4_entries").insert(entry)
                                }

                                Toast.makeText(context, "Entry saved successfully!", Toast.LENGTH_LONG).show()
                                
                                // Reset form except dates
                                regNo = ""
                                beneficiaryName = ""
                                bookNo = ""
                                receiptNo = ""
                                amount = ""

                            } catch (e: Exception) {
                                e.printStackTrace()
                                Toast.makeText(context, "Error: ${e.message}", Toast.LENGTH_LONG).show()
                            } finally {
                                isLoading = false
                            }
                        }
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(56.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = primaryTeal),
                    shape = RoundedCornerShape(100.dp),
                    enabled = !isLoading
                ) {
                    if (isLoading) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Saving...")
                    } else {
                        Icon(Icons.Default.Save, contentDescription = null)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Save Entry", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DatePickerField(
    label: String,
    date: String,
    onDateSelected: (String) -> Unit,
    modifier: Modifier = Modifier,
    primaryColor: Color
) {
    val context = LocalContext.current
    val calendar = Calendar.getInstance()

    // Parse existing date if available to set calendar
    if (date.isNotBlank()) {
        try {
            val parts = date.split("-")
            if (parts.size == 3) {
                calendar.set(parts[0].toInt(), parts[1].toInt() - 1, parts[2].toInt())
            }
        } catch (e: Exception) {
            // ignore
        }
    }

    val datePickerDialog = DatePickerDialog(
        context,
        { _, year, month, dayOfMonth ->
            val formattedDate = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth)
            onDateSelected(formattedDate)
        },
        calendar.get(Calendar.YEAR),
        calendar.get(Calendar.MONTH),
        calendar.get(Calendar.DAY_OF_MONTH)
    )

    OutlinedTextField(
        value = date,
        onValueChange = { },
        label = { Text(label) },
        readOnly = true,
        modifier = modifier,
        singleLine = true,
        trailingIcon = {
            IconButton(onClick = { datePickerDialog.show() }) {
                Icon(Icons.Default.CalendarToday, contentDescription = "Select Date", tint = primaryColor)
            }
        },
        colors = OutlinedTextFieldDefaults.colors(focusedTextColor = MaterialTheme.colorScheme.onSurface, unfocusedTextColor = MaterialTheme.colorScheme.onSurface, 
            focusedBorderColor = primaryColor, 
            focusedLabelColor = primaryColor
        ),
        enabled = true
    )
}
