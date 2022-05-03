package com.example.tutorialappfinal;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;

public class QuizViewActivity extends AppCompatActivity {
    private static final int REQUEST_CODE_QUIZ = 1;
    Button btn1;
    Button btn2;
    private TextView textViewHighscore;
    private TextView textViewScoreComment;
     int counts;



    @Override
    protected void onCreate(Bundle savedInstanceState) {

        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_quiz_view);
        textViewHighscore = findViewById(R.id.text_view_highscore);
        textViewScoreComment = findViewById(R.id.text_view_score_comment);
        SharedPreferences prefs = getSharedPreferences("sharedPrefs", Context.MODE_PRIVATE);
        int score = prefs.getInt("score",0);

        textViewHighscore.setText("Score: " + score);
        btn1 = findViewById(R.id.button_start_quiz);
        btn2 = findViewById(R.id.button_no_quiz);
        btn1.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {

                counts++;
                SharedPreferences prefs1 = getSharedPreferences("roundsN", Context.MODE_PRIVATE);
                SharedPreferences.Editor editor = prefs1.edit();
                editor.putInt("rounds", counts);
                editor.apply();
                Intent intent = new Intent(QuizViewActivity.this, QuizAnswerActivity.class);
                startActivityForResult(intent, REQUEST_CODE_QUIZ);
                finish();
            }
        });
        btn2.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(QuizViewActivity.this, MainHomeActivity.class);
                startActivity(intent);
                finish();
            }
        });

    }





}
