import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
import { FamiliesPage } from './families.page';

describe('FamiliesPage', () => {
  let component: FamiliesPage;
  let fixture: ComponentFixture<FamiliesPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FamiliesPage],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(FamiliesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
