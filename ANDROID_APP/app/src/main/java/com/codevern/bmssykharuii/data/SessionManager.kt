package com.codevern.bmssykharuii.data

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "settings")

class SessionManager(private val context: Context) {

    companion object {
        val AGENT_ID = stringPreferencesKey("agent_id")
        val AGENT_NAME = stringPreferencesKey("agent_name")
        val AGENT_AREA = stringPreferencesKey("agent_area")
        val THEME_MODE = stringPreferencesKey("theme_mode")
    }

    val loggedInAgentId: Flow<String?> = context.dataStore.data.map { preferences ->
        preferences[AGENT_ID]
    }

    val themeMode: Flow<String> = context.dataStore.data.map { preferences ->
        preferences[THEME_MODE] ?: "System Default"
    }

    suspend fun saveAgentSession(agent: Agent) {
        context.dataStore.edit { preferences ->
            preferences[AGENT_ID] = agent.id
            preferences[AGENT_NAME] = agent.name
            preferences[AGENT_AREA] = agent.area
        }
    }

    suspend fun clearSession() {
        context.dataStore.edit { preferences ->
            preferences.remove(AGENT_ID)
            preferences.remove(AGENT_NAME)
            preferences.remove(AGENT_AREA)
            // intentionally not clearing THEME_MODE so preferences persist
        }
    }

    suspend fun saveThemeMode(mode: String) {
        context.dataStore.edit { preferences ->
            preferences[THEME_MODE] = mode
        }
    }
}
