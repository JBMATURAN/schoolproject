package com.example.tutorialappfinal;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;

import androidx.appcompat.app.AppCompatActivity;

public class StudySelect extends AppCompatActivity {
    Button btn1;
    Button btn2;
    Button btn3;
    Button btn4;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_study_select);


        btn1 = (Button) findViewById(R.id.selec_study1);
        btn1.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(StudySelect.this, StudyActivity.class);
                startActivity(intent);
            }
        });

        btn2 = (Button) findViewById(R.id.selec_study2);
        btn2.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(StudySelect.this, StudyActivity1.class);
                startActivity(intent);
            }
        });

        btn3 = (Button) findViewById(R.id.selec_study3);
        btn3.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(StudySelect.this, StudyActivity2.class);
                startActivity(intent);
            }
        });

        btn4 = (Button) findViewById(R.id.selec_study4);
        btn4.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(StudySelect.this, StudyActivity3.class);
                startActivity(intent);
            }
        });

    }
}