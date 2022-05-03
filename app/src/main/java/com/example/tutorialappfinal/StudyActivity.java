package com.example.tutorialappfinal;

import androidx.appcompat.app.AppCompatActivity;

import android.os.Bundle;

import com.github.barteksc.pdfviewer.PDFView;

public class StudyActivity extends AppCompatActivity {
    PDFView study1;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_study);

        study1=(PDFView) findViewById(R.id.pdfView1);
        study1.fromAsset("shapes-basic1.pdf").load();
    }
}