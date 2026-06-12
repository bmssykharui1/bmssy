package com.codevern.bmssykharuii.ui.screens

import android.util.Log
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.codevern.bmssykharuii.network.SupabaseApi
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Count
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.time.LocalDate
import java.time.ZoneId
import kotlinx.serialization.Serializable

@Serializable
data class PfUpdateLocal(
    val approved_ssin: String? = null,
    val reason: String? = null
)

@Composable
fun DashboardOverviewScreen(agentId: String) {
    var count142 by remember { mutableStateOf<Long?>(null) }
    var count242 by remember { mutableStateOf<Long?>(null) }
    var newCount142 by remember { mutableStateOf<Long?>(null) }
    var newCount242 by remember { mutableStateOf<Long?>(null) }
    
    var pf142 by remember { mutableStateOf<Long?>(null) }
    var pf242 by remember { mutableStateOf<Long?>(null) }
    var totalRejected by remember { mutableStateOf<Long?>(null) }
    
    var totalToday by remember { mutableStateOf<Long?>(null) }
    var totalYesterday by remember { mutableStateOf<Long?>(null) }
    var totalThisMonth by remember { mutableStateOf<Long?>(null) }

    LaunchedEffect(Unit) {
        withContext(Dispatchers.IO) {
            try {
                val zoneId = ZoneId.of("Asia/Kolkata")
                val today = LocalDate.now(zoneId)
                val yesterday = today.minusDays(1)
                val startOfMonth = today.withDayOfMonth(1)
                val nextMonth = startOfMonth.plusMonths(1)

                val todayStr = today.toString()
                val yesterdayStr = yesterday.toString()
                val startMonthStr = startOfMonth.toString()
                val nextMonthStr = nextMonth.toString()

                // OTHERS (142...)
                count142 = SupabaseApi.client.from("beneficiaries").select {
                    filter { like("approved_ssin", "142%") }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

                // CONTRACTIONS (242...)
                count242 = SupabaseApi.client.from("beneficiaries").select {
                    filter { like("approved_ssin", "242%") }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

                // NEW OTHERS
                newCount142 = SupabaseApi.client.from("beneficiaries").select {
                    filter { 
                        like("approved_ssin", "142%")
                        gte("created_at", startMonthStr)
                    }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

                // NEW CONST.
                newCount242 = SupabaseApi.client.from("beneficiaries").select {
                    filter { 
                        like("approved_ssin", "242%")
                        gte("created_at", startMonthStr)
                    }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

                // Fetch all pf_updates for this month to filter locally and avoid NULL syntax issues
                val monthPfUpdates = SupabaseApi.client.from("pf_update").select {
                    filter { 
                        gte("date", startMonthStr)
                    }
                }.decodeList<PfUpdateLocal>()

                // PF OTHERS
                pf142 = monthPfUpdates.count { 
                    it.reason == null && it.approved_ssin?.startsWith("142") == true 
                }.toLong()

                // PF CONTRACTIONS
                pf242 = monthPfUpdates.count { 
                    it.reason == null && it.approved_ssin?.startsWith("242") == true 
                }.toLong()

                // PF REJECTED
                totalRejected = monthPfUpdates.count { 
                    it.reason != null 
                }.toLong()

                // NEW ADD (TODAY)
                totalToday = SupabaseApi.client.from("beneficiaries").select {
                    filter { 
                        gte("created_at", todayStr)
                    }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

                // NEW ADD (YESTERDAY)
                totalYesterday = SupabaseApi.client.from("beneficiaries").select {
                    filter { 
                        gte("created_at", yesterdayStr)
                        lt("created_at", todayStr)
                    }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

                // NEW ADD (MONTH)
                totalThisMonth = SupabaseApi.client.from("beneficiaries").select {
                    filter { 
                        gte("created_at", startMonthStr)
                    }
                    count(Count.EXACT)
                }.countOrNull() ?: 0L

            } catch (e: Exception) {
                Log.e("Dashboard", "Failed to fetch dashboard stats", e)
                count142 = 0L
                count242 = 0L
                newCount142 = 0L
                newCount242 = 0L
                pf142 = 0L
                pf242 = 0L
                totalRejected = 0L
                totalToday = 0L
                totalYesterday = 0L
                totalThisMonth = 0L
            }
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp)
    ) {
        Text(
            text = "SSIN Statistics",
            fontSize = 18.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onBackground,
            modifier = Modifier.padding(bottom = 8.dp)
        )

        LazyVerticalGrid(
            columns = GridCells.Fixed(2),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.weight(1f)
        ) {
            item { SimpleCard("OTHERS", count142, Color(0xFF16A34A)) }
            item { SimpleCard("CONTRACTIONS", count242, Color(0xFFD97706)) }
            item { SimpleCard("NEW OTHERS", newCount142, Color(0xFF0284C7)) }
            item { SimpleCard("NEW CONST.", newCount242, Color(0xFFDC2626)) }
            
            item { SimpleCard("PF OTHERS", pf142, Color(0xFF16A34A), Icons.Default.CheckCircle) }
            item { SimpleCard("PF CONST.", pf242, Color(0xFFD97706), Icons.Default.List) }
            
            item { SimpleCard("PF REJECTED", totalRejected, Color(0xFFDC2626), Icons.Default.Cancel) }
            
            item { SimpleCard("ADD (TODAY)", totalToday, Color(0xFF0284C7), Icons.Default.PersonAdd) }
            item { SimpleCard("ADD (YESTERDAY)", totalYesterday, Color(0xFF6750A4), Icons.Default.People) }
            item { SimpleCard("ADD (MONTH)", totalThisMonth, Color(0xFFB3261E), Icons.Default.Dashboard) }
        }
    }
}

@Composable
fun SimpleCard(
    title: String,
    value: Long?,
    color: Color,
    icon: ImageVector? = null
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(4.dp, RoundedCornerShape(12.dp)),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            horizontalAlignment = Alignment.Start
        ) {
            Text(
                text = title,
                fontSize = 12.sp,
                fontWeight = FontWeight.Bold,
                color = color,
                letterSpacing = 0.5.sp
            )
            Spacer(modifier = Modifier.height(8.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                if (icon != null) {
                    Icon(imageVector = icon, contentDescription = null, tint = color, modifier = Modifier.size(24.dp).padding(end = 4.dp))
                }
                Text(
                    text = value?.toString() ?: "...",
                    fontSize = 24.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = MaterialTheme.colorScheme.onSurface
                )
            }
        }
    }
}
