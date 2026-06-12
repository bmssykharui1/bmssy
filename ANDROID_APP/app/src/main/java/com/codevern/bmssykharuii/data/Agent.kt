package com.codevern.bmssykharuii.data

import kotlinx.serialization.Serializable

@Serializable
data class Agent(
    val id: String,
    val name: String,
    val area: String,
    val created_at: String? = null
)
