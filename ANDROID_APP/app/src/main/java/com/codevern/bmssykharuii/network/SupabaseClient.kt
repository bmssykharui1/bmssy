package com.codevern.bmssykharuii.network

import io.github.jan.supabase.SupabaseClient
import io.github.jan.supabase.createSupabaseClient
import io.github.jan.supabase.postgrest.Postgrest

object SupabaseApi {
    val client: SupabaseClient = createSupabaseClient(
        supabaseUrl = "https://yachsrzzgolxskqxdthe.supabase.co",
        supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlhY2hzcnp6Z29seHNrcXhkdGhlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODExODgxNjMsImV4cCI6MjA5Njc2NDE2M30.AVb7QySPXgKDneSY5TinqLo3KLm9srYUvPWxBo75mvk"
    ) {
        install(Postgrest)
        defaultSerializer = io.github.jan.supabase.serializer.KotlinXSerializer(
            kotlinx.serialization.json.Json {
                ignoreUnknownKeys = true
                coerceInputValues = true
            }
        )
    }
}
