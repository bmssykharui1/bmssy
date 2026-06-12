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
    }

    val loggedInAgentId: Flow<String?> = context.dataStore.data.map { preferences ->
        preferences[AGENT_ID]
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
            preferences.clear()
        }
    }
}
