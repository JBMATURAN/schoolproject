package com.example.tutorialappfinal;

import androidx.appcompat.app.AppCompatActivity;

import android.os.Bundle;

import com.github.barteksc.pdfviewer.PDFView;

public class StudyActivity2 extends AppCompatActivity {
    PDFView study1;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_study2);

        study1=(PDFView) findViewById(R.id.pdfView3);
        study1.fromAsset("shapes-geom.pdf").load();
    }
}