import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column, computed } from '@ioc:Adonis/Lucid/Orm'
import UnreadEmail from './UnreadEmail'

export default class UnreadEmailActivity extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public unreadEmailId: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => UnreadEmail)
  public unreadEmail: BelongsTo<typeof UnreadEmail>

  @computed()
  public get date() {
    return this.createdAt.toLocaleString(DateTime.DATE_MED)
  }

  public get time() {
    return this.createdAt.toRelative()
  }

  public get serializedUnreadEmailActivity() {
    const { id, unreadEmailId, date, time } = this

    return {
      id,
      unreadEmailId,
      date,
      time,
    }
  }
}
