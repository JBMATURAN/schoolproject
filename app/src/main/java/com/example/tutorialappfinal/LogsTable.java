package com.example.tutorialappfinal;

import android.provider.BaseColumns;

public class LogsTable {
    private LogsTable(){}
    public static class TableLogs implements BaseColumns{
    public static final String LOGS_NAME = "user_logs";
    public static final String COLUMN_ANSWER = "answer";
    public static final String COLUMN_HINT = "hint";
    public static final String COLUMN_TOPICS = "topics";
    public static final String COLUMN_TIME = "time";
    }
}
