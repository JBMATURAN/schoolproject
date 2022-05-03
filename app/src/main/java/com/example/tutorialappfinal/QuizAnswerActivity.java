package com.example.tutorialappfinal;

import android.content.ContentValues;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.res.ColorStateList;
import android.database.sqlite.SQLiteDatabase;
import android.graphics.Color;
import android.os.Bundle;
import android.os.SystemClock;
import android.view.View;
import android.widget.Button;
import android.widget.Chronometer;
import android.widget.ImageView;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

public class QuizAnswerActivity extends AppCompatActivity {
    HintDialog hintDialog = new HintDialog();
    private Button btnHint;
    private Chronometer chronometer;
    private static ImageView imageview;
    private int[] images= {R.drawable.one,R.drawable.two,R.drawable.three,R.drawable.four,R.drawable.five,R.drawable.six,R.drawable.octagon,R.drawable.star};
    private Button buttonBook;
    SharedPreferences prefs;
    private TextView textViewQuestion;
    private TextView textViewScore;
    private TextView textViewWscore;
    private TextView textViewQuestionCount;
    private TextView textViewCountDown;
    private RadioGroup rbGroup;
    private RadioButton rb1;
    private RadioButton rb2;
    private RadioButton rb3;
    private Button buttonConfirmNext;
    private ColorStateList textColorDefaultRb;
    private List<Question> questionList;
    private int questionCounter;
    private int questionCountTotal;
    private Question currentQuestion;
    private int score;
    private int Wscore;
    int answerNr;
    int hint,topics;
    String chronoText;
    private boolean answered;
    private long backPressedTime;
    private static final String FILE_NAME="Logs.txt";
    private String Logs;
    SharedPreferences prefs1;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_quiz_answer);
        btnHint = findViewById(R.id.btn_hint);
        btnHint.setText("Hint");



        chronometer = findViewById(R.id.text_view_countdown);

        chronometer.start();


        imageview = (ImageView)findViewById(R.id.imageView);
        //For guiding user
        buttonBook = findViewById(R.id.button_study_back);

        //For guiding user end of code
        textViewQuestion = findViewById(R.id.text_view_question);
        textViewScore = findViewById(R.id.text_view_score);
        textViewWscore= findViewById(R.id.text_view_wrong_answer);

        textViewQuestionCount = findViewById(R.id.text_view_question_count);
        textViewCountDown = findViewById(R.id.text_view_countdown);
        rbGroup = findViewById(R.id.radio_group);
        rb1 = findViewById(R.id.radio_button1);
        rb2 = findViewById(R.id.radio_button2);
        rb3 = findViewById(R.id.radio_button3);
        buttonConfirmNext = findViewById(R.id.button_confirm_next);
        textColorDefaultRb = rb1.getTextColors();





        //get all question
        QuizDbHelper dbHelper = new QuizDbHelper(this);
        questionList = dbHelper.getAllQuestions();
        questionCountTotal = questionList.size();
        Collections.shuffle(questionList);
        showNextQuestion();
        buttonConfirmNext.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                chronoText = chronometer.getText().toString();// get value in time
                if (!answered) {
                    if (rb1.isChecked() || rb2.isChecked() || rb3.isChecked()) {
                        checkAnswer();
                    } else {
                        Toast.makeText(QuizAnswerActivity.this, "Please select an answer", Toast.LENGTH_SHORT).show();
                    }
                } else {


                    showNextQuestion();
                }
            }
        });
    }

    private void showNextQuestion() {
        hint=0;
        topics=0;
        chronometer.setBase(SystemClock.elapsedRealtime());
        rb1.setTextColor(textColorDefaultRb);
        rb2.setTextColor(textColorDefaultRb);
        rb3.setTextColor(textColorDefaultRb);
        rbGroup.clearCheck();

        if (questionCounter < questionCountTotal) {
            currentQuestion = questionList.get(questionCounter);
            textViewQuestion.setText("Identify the shape of obejct shown");
            imageview.setImageResource(images[currentQuestion.getPic_id()-1]);//showing image
            //Hint button
            btnHint.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {
                    hint++;
                    if(currentQuestion.getAnswerNr()==1){
                        hintDialog.setMessage(currentQuestion.getHint().toUpperCase());
                    }
                    if(currentQuestion.getAnswerNr()==2){

                        hintDialog.setMessage(currentQuestion.getHint().toUpperCase());
                    }
                    if(currentQuestion.getAnswerNr()==3){

                        hintDialog.setMessage(currentQuestion.getHint().toUpperCase());
                    }
                    openDialog1();
                    buttonBook.setOnClickListener(new View.OnClickListener() {
                        @Override
                        public void onClick(View v) {
                            topics++;
                            Intent intent = new Intent(QuizAnswerActivity.this, StudyActivity.class);
                            startActivity(intent);
                        }
                    });
                }
            });
            //end of code
            rb1.setText(currentQuestion.getOption1());
            rb2.setText(currentQuestion.getOption2());
            rb3.setText(currentQuestion.getOption3());

            questionCounter++;
            textViewQuestionCount.setText("Question: " + questionCounter + "/" + questionCountTotal);
            answered = false;
            buttonConfirmNext.setText("Confirm");
        } else {
            finishQuiz();
        }
    }

    private void checkAnswer() {
        answered = true;

        RadioButton rbSelected = findViewById(rbGroup.getCheckedRadioButtonId());
         answerNr = rbGroup.indexOfChild(rbSelected) + 1;

        if (answerNr == currentQuestion.getAnswerNr()) {
            score++;
            textViewScore.setText("Score: " + score);

        }else {
            Wscore++;
            textViewWscore.setText("Wrong: "+Wscore);
            if(Wscore==2){
                openDialog();
            }
        }
        showSolution();
    }
    private void showSolution() {
        rb1.setTextColor(Color.RED);
        rb2.setTextColor(Color.RED);
        rb3.setTextColor(Color.RED);
        switch (currentQuestion.getAnswerNr()) {
            case 1:
                rb1.setTextColor(Color.GREEN);
                if (currentQuestion.getAnswerNr() == answerNr) {
                    textViewQuestion.setText("Correct answer");
                } else {
                    textViewQuestion.setText("You pick a wrong asnwer the\ncorrect answer is " + rb1.getText());
                }
                break;
            case 2:
                rb2.setTextColor(Color.GREEN);
                if (currentQuestion.getAnswerNr() == answerNr) {
                    textViewQuestion.setText("Correct answer");
                } else {
                    textViewQuestion.setText("You pick a wrong asnwer the\ncorrect answer is " + rb2.getText());
                }
                break;
            case 3:
                rb3.setTextColor(Color.GREEN);
                if (currentQuestion.getAnswerNr() == answerNr) {
                    textViewQuestion.setText("Correct answer");
                } else {
                    textViewQuestion.setText("You pick a wrong asnwer the\ncorrect answer is " + rb3.getText());
                }
                break;
        }
        if (questionCounter < questionCountTotal) {
            buttonConfirmNext.setText("Next");
        } else {
            buttonConfirmNext.setText("Finish");
        }
        prefs1 = getSharedPreferences("roundsN", Context.MODE_PRIVATE);
        int rounds = prefs1.getInt("rounds", 0);
        if (questionCounter == 1) {
            Logs = "Round " + (rounds + 1) + "\n" + String.valueOf(currentQuestion.getPic_id()) + "/" + currentQuestion.getQuestion() + "/" + textViewQuestion.getText() + "/" + String.valueOf(hint) + "/" + String.valueOf(topics)
                    + "/" + chronoText;
        }else {
            Logs = String.valueOf(currentQuestion.getPic_id()) + "/" + currentQuestion.getQuestion() + "/" + textViewQuestion.getText() + "/" + String.valueOf(hint) + "/" + String.valueOf(topics)
                    + "/" + chronoText;
        }
        FileOutputStream fos = null;
        try {
            fos = openFileOutput(FILE_NAME,MODE_APPEND);
            fos.write(Logs.getBytes());
            fos.flush();
            fos.write("\n".getBytes());
            fos.flush();
            if(questionCounter==8){
                fos.write("Total Score: ".getBytes());
                fos.flush();
                fos.write(String.valueOf(score).getBytes());
                fos.flush();
                fos.write("\n".getBytes());
                fos.flush();
                Toast.makeText(this,"Saved to "+getFilesDir() + "/"+ FILE_NAME,Toast.LENGTH_LONG).show();
            }
        } catch (FileNotFoundException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        }finally {
            if(fos!=null){
                try {
                    fos.close();
                } catch (IOException e) {
                    e.printStackTrace();
                }
            }
        }
    }
    private void finishQuiz() {
        if(score>3){
            Intent intent = new Intent(QuizAnswerActivity.this,EndActivity.class);
            startActivity(intent);
            finish();
        }else {
            Intent intent = new Intent(QuizAnswerActivity.this, QuizViewActivity.class);
            startActivity(intent);
            prefs = getSharedPreferences("sharedPrefs", Context.MODE_PRIVATE);
            SharedPreferences.Editor editor = prefs.edit();
            editor.putInt("score", score);
            editor.apply();
            finish();
        }
    }

    @Override
    public void onBackPressed() {
        if (backPressedTime + 2000 > System.currentTimeMillis()){
            finishQuiz();
        }else {
             Toast.makeText(this,"Press back again to finish",Toast.LENGTH_SHORT).show();
        }
        backPressedTime = System.currentTimeMillis();
    }
    //Help the user
    public void openDialog(){
        ExampleDialog exampleDialog = new ExampleDialog();
        exampleDialog.show(getSupportFragmentManager(),"example dialog");
    }
    public void openDialog1(){
       hintDialog.show(getSupportFragmentManager(),"example dialog");
    }

    //to jumble the words
    public static String Scramble(String ch)
    {
        ArrayList<Character> array = new ArrayList<Character>();
        String Scrambled = "";
        for (int b = 0; b < ch.length() ; b++){
            array.add(ch.charAt(b));
        }
        Collections.shuffle(array);
        for (int k = 0; k < array.size(); k++)
        {
            Scrambled += array.get(k);
        }
        return Scrambled;
    }
    //end of code
}



